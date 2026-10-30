<?php

namespace App\Support;

class AdultNutritionalCompositionCatalogV2
{
    /**
     * Catálogo entregado para población adulta. Cada alternativa incluye
     * aminoácidos; las letras representan los demás grupos presentes.
     */
    private const BLUEPRINT = <<<'CATALOG'
1:T;2:V;3:M;4:E,T;5:E,V;6:E,M,!I;7:E,T,V;8:E,T,V,M,!I;9:E,V,M,!I;10:E,T,M,!I;11:T,V;12:V,M,!I
17:W;18:W,E;19:W,T;20:W,V;21:W,M;22:W,E,T;23:W,E,V;24:W,E,M,!I;25:W,E,T,V;26:W,E,T,V,M,!I;27:W,E,V,M,!I;28:W,E,T,M,!I;29:W,T,V;30:W,T,M,!I;31:W,T,V,M,!I;32:W,V,M,!I
33:S;34:S,E;35:S,T;36:S,V;37:S,M;38:S,E,T;39:S,E,V;40:S,E,M,!I;41:S,E,T,V;42:S,E,T,V,M,!I;43:S,E,V,M,!I;44:S,E,T,M,!I;45:S,T,V;46:S,T,M,!I;47:S,T,V,M,!I;48:S,V,M,!I
49:W,S;50:W,S,E;51:W,S,T;52:W,S,V;53:W,S,M;54:W,S,E,T;55:W,S,E,V;56:W,S,E,M,!I;57:W,S,E,T,V;58:W,S,E,T,V,M,!I;59:W,S,E,V,M,!I;60:W,S,E,T,M,!I;61:W,S,T,V;62:W,S,T,M,!I;63:W,S,T,V,M,!I;64:W,S,V,M,!I
66:D,M;67:D,V;70:D,T,M;71:D,T,V;72:D,T,V,M;73:D,E;74:D,E,M;75:D,E,V;76:D,E,V,M;78:D,E,T,M
82:D,L,M;83:D,L,V;84:D,L,V,M;85:D,L,T;86:D,L,T,M;87:D,L,T,V;88:D,L,T,V,M;90:D,L,E,M;91:D,L,E,V;94:D,L,E,T,M;95:D,L,E,T,V
97:D,S;98:D,S,M;99:D,S,V;100:D,S,V,M;101:D,S,T;102:D,S,T,M;103:D,S,T,V;104:D,S,T,V,M;105:D,S,E;106:D,S,E,M;107:D,S,E,V;108:D,S,E,V,M;109:D,S,E,T;110:D,S,E,T,M;111:D,S,E,T,V;112:D,S,E,T,V,M
113:D,S,L;114:D,S,L,M;115:D,S,L,V;116:D,S,L,V,M;117:D,S,L,T;118:D,S,L,T,M;119:D,S,L,T,V;120:D,S,L,T,V,M;121:D,S,L,E;122:D,S,L,E,M;123:D,S,L,E,V;124:D,S,L,E,V,M;125:D,S,L,E,T;126:D,S,L,E,T,M;127:D,S,L,E,T,V;128:D,S,L,E,T,V,M
131:D,W,M;132:D,W,V;133:D,W,V,M;134:D,W,T;135:D,W,T,M;136:D,W,T,V;137:D,W,T,V,M;139:D,W,E,M;142:D,W,E,T;143:D,W,E,T,M
147:D,W,L,M;148:D,W,L,V;149:D,W,L,V,M;150:D,W,L,T;151:D,W,L,T,M;152:D,W,L,T,V;153:D,W,L,T,V,M;155:D,W,L,E,M;156:D,W,L,E,V;157:D,W,L,E,V,M;159:D,W,L,E,T,M
163:D,W,S,M;164:D,W,S,V;165:D,W,S,V,M;166:D,W,S,T;167:D,W,S,T,M;168:D,W,S,T,V;169:D,W,S,T,V,M;171:D,W,S,E,M;172:D,W,S,E,V;173:D,W,S,E,V,M;175:D,W,S,E,T,M
178:D,W,S,L;179:D,W,S,L,M;180:D,W,S,L,V;181:D,W,S,L,V,M;182:D,W,S,L,T;183:D,W,S,L,T,M;184:D,W,S,L,T,V;185:D,W,S,L,T,V,M;187:D,W,S,L,E,M;191:D,W,S,L,E,T,M;193:D,W,S,L,E,T,V,M
CATALOG;

    private const GROUPS = [
        'D' => ['dextrose', 'dextrosa'],
        'W' => ['water', 'agua estéril para inyección'],
        'S' => ['saline', 'cloruro de sodio al 0.9 %'],
        'L' => ['lipids', 'lípidos intravenosos'],
        'E' => ['electrolytes', 'electrolitos añadidos por separado'],
        'T' => ['trace_elements', 'elementos traza'],
        'V' => ['vitamins', 'vitaminas'],
        'M' => ['medications', 'medicamentos'],
    ];

    public static function compositions(): array
    {
        $items = [];

        foreach (preg_split('/[;\r\n]+/', trim(self::BLUEPRINT)) as $definition) {
            if (! str_contains($definition, ':')) {
                continue;
            }

            [$sourceNumber, $tokens] = array_map('trim', explode(':', $definition, 2));
            $tokens = array_values(array_filter(array_map('trim', explode(',', $tokens))));
            $forbidInsulin = in_array('!I', $tokens, true);
            $tokens = array_values(array_diff($tokens, ['!I']));
            $groups = ['amino_acids'];
            $labels = ['Aminoácidos'];

            foreach ($tokens as $token) {
                if (! isset(self::GROUPS[$token])) {
                    continue;
                }
                [$group, $label] = self::GROUPS[$token];
                $groups[] = $group;
                $labels[] = $label;
            }

            $items[] = [
                'code' => 'AD2-'.str_pad($sourceNumber, 3, '0', STR_PAD_LEFT),
                'label' => implode(' + ', $labels).($forbidInsulin ? ' (excepto insulina)' : ''),
                'groups' => $groups,
                'component_terms' => [],
                'forbidden_component_terms' => $forbidInsulin ? ['insulin'] : [],
            ];
        }

        return $items;
    }
}
