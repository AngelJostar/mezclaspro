<?php

namespace App\Services\Agents;

use App\Models\AiAgent;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

class OperationalAgents
{
    public const CONCILIATION = 'scheduled_conciliation';
    public const NUTRITION = 'patient_nutrition_comparison';
    public const TIMEZONE = 'America/Mexico_City';

    public static function handles(AiAgent $agent): bool
    {
        return in_array($agent->integration_key, [self::CONCILIATION, self::NUTRITION], true);
    }

    public static function defaults(): array
    {
        return ['activation' => 'monthly', 'frequency' => 'monthly', 'day' => 1, 'weekday' => 1, 'time' => '09:00',
            'hospital_ids' => [], 'email_enabled' => false, 'hospital_emails' => [], 'institution_emails' => []];
    }

    public static function validate(array $input): array
    {
        $data = validator($input, [
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'day' => 'required|integer|between:1,28', 'weekday' => 'required|integer|between:1,7', 'time' => 'required|date_format:H:i',
            'hospital_ids' => 'required|array|min:1|max:1000', 'hospital_ids.*' => 'integer|distinct|exists:hospitals,id',
            'email_enabled' => 'required|boolean',
            'hospital_emails' => 'present|array', 'hospital_emails.*' => 'nullable|email:rfc|max:254',
            'institution_emails' => 'present|array', 'institution_emails.*' => 'nullable|email:rfc|max:254',
        ])->validate();
        $data['hospital_ids'] = array_map('intval', $data['hospital_ids']);
        $data['activation'] = $data['frequency'];
        return $data;
    }

    public static function next(array $config, ?CarbonImmutable $after = null): CarbonImmutable
    {
        $after = ($after ?? CarbonImmutable::now(self::TIMEZONE))->setTimezone(self::TIMEZONE);
        [$hour, $minute] = array_map('intval', explode(':', $config['time']));
        $candidate = $after->setTime($hour, $minute);
        if ($config['frequency'] === 'monthly') {
            $candidate = $candidate->day((int) $config['day']);
            return $candidate->gt($after) ? $candidate : $candidate->addMonthNoOverflow();
        }
        if ($config['frequency'] === 'weekly') {
            $candidate = $candidate->startOfWeek()->addDays((int) $config['weekday'] - 1)->setTime($hour, $minute);
            return $candidate->gt($after) ? $candidate : $candidate->addWeek();
        }
        return $candidate->gt($after) ? $candidate : $candidate->addDay();
    }

    public static function period(array $config, CarbonImmutable $at): array
    {
        $at = $at->setTimezone(self::TIMEZONE);
        [$from, $to] = match ($config['frequency']) {
            'daily' => [$at->subDay(), $at->subDay()],
            'weekly' => [$at->subWeek()->startOfWeek(), $at->subWeek()->endOfWeek()],
            default => [$at->subMonthNoOverflow()->startOfMonth(), $at->subMonthNoOverflow()->endOfMonth()],
        };
        return ['desde' => $from->toDateString(), 'hasta' => $to->toDateString()];
    }
}
