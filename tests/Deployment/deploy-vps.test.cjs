const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const bash = process.platform === 'win32' ? 'C:/Program Files/Git/bin/bash.exe' : 'bash';
const script = path.resolve(__dirname, '../../scripts/deploy-vps.sh');
const sha = 'a'.repeat(40);
const key = 'sk-test-deploy-not-a-real-secret';
const unix = value => value.replaceAll('\\', '/').replace(/^([A-Za-z]):/, (_, drive) => '/' + drive.toLowerCase());

function fixture(run) {
    const root = fs.mkdtempSync(path.join(os.tmpdir(), 'mezclaspro-deploy-'));
    const app = path.join(root, 'app');
    const bin = path.join(root, 'bin');
    const log = path.join(root, 'commands.log');
    fs.mkdirSync(path.join(app, 'storage/framework'), { recursive: true });
    fs.mkdirSync(path.join(app, 'public'), { recursive: true });
    fs.mkdirSync(path.join(app, 'vendor'), { recursive: true });
    fs.mkdirSync(bin);
    fs.writeFileSync(path.join(app, 'artisan'), 'synthetic application');
    fs.writeFileSync(path.join(app, 'vendor/autoload.php'), 'synthetic autoload');
    fs.writeFileSync(path.join(app, '.env'), 'APP_KEY=synthetic-server-key\n');
    const executable = (name, content) => fs.writeFileSync(path.join(bin, name), '#!/usr/bin/env bash\nset -eu\n' + content, { mode: 0o755 });
    executable('git', `
printf 'git:%s\\n' "$*" >> "$TEST_LOG"
case "$*" in
  'rev-parse --show-toplevel') printf '%s\\n' "$TEST_APP_ROOT" ;;
  'branch --show-current') printf '%s\\n' "\${TEST_BRANCH:-main}" ;;
  'remote get-url origin') printf '%s\\n' "\${TEST_ORIGIN:-https://github.com/owner/repo.git}" ;;
  'diff --quiet'|'diff --cached --quiet') [[ \${TEST_DIRTY:-0} = 0 ]] ;;
  'ls-files --error-unmatch .env') exit 1 ;;
  'fetch --no-tags origin main') ;;
  'rev-parse refs/remotes/origin/main') printf '%s\\n' "\${TEST_REMOTE_SHA:-${sha}}" ;;
  'merge-base --is-ancestor HEAD '*|'merge --ff-only '*) ;;
  *) exit 90 ;;
esac
`);
    executable('php', `
printf 'php:%s\\n' "$*" >> "$TEST_LOG"
if [[ $2 = agents:configure-openai ]]; then
  incoming=$(cat)
  [[ $incoming = '${key}' ]]
  printf 'provider:stdin-ok\\n' >> "$TEST_LOG"
fi
if [[ $2 = down ]]; then touch storage/framework/down; fi
if [[ $2 = up ]]; then rm -f storage/framework/down; fi
if [[ $2 = \${TEST_FAIL_COMMAND:-not-a-command} ]]; then exit 1; fi
`);
    for (const command of ['composer', 'npm', 'flock']) {
        executable(command, `printf '${command}:%s\\n' "$*" >> "$TEST_LOG"\n`);
    }
    const execute = (env = {}, args = []) => spawnSync(bash, ['--noprofile', '--norc', '-c', 'export PATH="$TEST_BIN:$PATH"; exec bash "$@"', '--', unix(script), unix(app), sha, 'owner/repo', ...args], {
        env: { ...process.env, TEST_BIN: unix(bin), TEST_APP_ROOT: unix(app), TEST_LOG: unix(log), ...env },
        input: key + '\n', encoding: 'utf8', timeout: 15000,
    });
    try { run({ execute, app, readLog: () => fs.existsSync(log) ? fs.readFileSync(log, 'utf8') : '' }); }
    finally {
        assert.equal(path.dirname(path.resolve(root)), path.resolve(os.tmpdir()));
        assert.ok(path.basename(root).startsWith('mezclaspro-deploy-'));
        fs.rmSync(root, { recursive: true, force: true });
    }
}

test('deployment updates dependencies, migrations and agent without leaking or replacing server secrets', () => fixture(({ execute, app, readLog }) => {
    const result = execute({}, ['gpt-4.1-mini']);
    assert.equal(result.status, 0, result.stderr);
    const log = readLog();
    assert.ok(log.includes('git:merge --ff-only ' + sha));
    assert.ok(log.indexOf('npm:run build') < log.indexOf('php:artisan migrate --force'));
    assert.ok(log.includes('php:artisan clinical:import-manual resources/clinical/manual-v4.docx --manual-version=4'));
    assert.ok(log.includes('provider:stdin-ok'));
    assert.ok(log.includes('--model=gpt-4.1-mini'));
    assert.ok(log.indexOf('provider:stdin-ok') < log.indexOf('php:artisan up'));
    assert.ok(!log.includes(key) && !result.stdout.includes(key) && !result.stderr.includes(key));
    assert.ok(!log.includes('key:generate') && !log.includes('migrate:fresh'));
    assert.equal(fs.readFileSync(path.join(app, '.env'), 'utf8'), 'APP_KEY=synthetic-server-key\n');
    assert.ok(!fs.existsSync(path.join(app, 'storage/framework/down')));
}));

test('dirty, unrelated and stale checkouts are rejected before maintenance or database operations', () => {
    for (const [env, expected] of [[{ TEST_DIRTY: '1' }, 'cambios locales'], [{ TEST_BRANCH: 'development' }, 'checkout del VPS'], [{ TEST_ORIGIN: 'https://github.com/other/repo.git' }, 'remoto origin'], [{ TEST_REMOTE_SHA: 'b'.repeat(40) }, 'ultimo de main']]) {
        fixture(({ execute, readLog }) => {
            const result = execute(env);
            assert.notEqual(result.status, 0);
            assert.ok(result.stderr.includes(expected), result.stderr);
            assert.ok(!readLog().includes('php:artisan down'));
            assert.ok(!readLog().includes('php:artisan migrate'));
        });
    }
});

test('migration failure stops the deploy and keeps the application in maintenance', () => fixture(({ execute, app, readLog }) => {
    const result = execute({ TEST_FAIL_COMMAND: 'migrate' });
    assert.notEqual(result.status, 0);
    assert.ok(fs.existsSync(path.join(app, 'storage/framework/down')));
    assert.ok(!readLog().includes('php:artisan up'));
    assert.ok(!readLog().includes('provider:stdin-ok'));
}));

test('an existing maintenance state is never cleared by deployment', () => fixture(({ execute, app, readLog }) => {
    fs.writeFileSync(path.join(app, 'storage/framework/down'), 'existing-maintenance');
    assert.notEqual(execute().status, 0);
    assert.equal(fs.readFileSync(path.join(app, 'storage/framework/down'), 'utf8'), 'existing-maintenance');
    assert.ok(!readLog().includes('php:artisan up'));
}));
