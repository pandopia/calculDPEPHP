/**
 * Décomposition de `enum_methode_application_dpe_log_id` en trois choix :
 * type de bien, type de chauffage, type d'ECS.
 *
 * L'énumération officielle croise ces trois dimensions dans un même libellé
 * (« dpe appartement individuel chauffage collectif ecs individuel »). On les
 * extrait des libellés du XSD : aucun code n'est inventé, chaque combinaison
 * proposée correspond à un code existant de la version.
 */
export interface MethodeEntry {
  code: string;
  bien: string;
  chauffage: string | null;
  ecs: string | null;
}

const SYSTEME = /(individuel|collectif|mixte \(collectif-individuel\))/;
const CHAUFFAGE = new RegExp(`\\s*chauffage ${SYSTEME.source}`);
const ECS = new RegExp(`\\s*ecs ${SYSTEME.source}`);

export function parseMethodes(labels: Record<string, string>): MethodeEntry[] {
  return Object.entries(labels).map(([code, label]) => {
    const ch = CHAUFFAGE.exec(label);
    const ecs = ECS.exec(label);
    return { code, bien: bienLabel(label.replace(CHAUFFAGE, '').replace(ECS, '').replace(/^dpe\s+/, '').trim()), chauffage: ch ? capitalize(ch[1]) : null, ecs: ecs ? capitalize(ecs[1]) : null };
  });
}

/** Valeurs distinctes, dans l'ordre des codes officiels. */
export function distinct(values: (string | null)[]): string[] {
  return [...new Set(values.filter((v): v is string => v !== null))];
}

export function findCode(entries: MethodeEntry[], bien: string, chauffage: string | null, ecs: string | null): string | null {
  return entries.find((e) => e.bien === bien && e.chauffage === chauffage && e.ecs === ecs)?.code ?? null;
}

/** Libellé court du type de bien, dérivé du libellé officiel. */
function bienLabel(raw: string): string {
  const etude = /^issu d'une étude .*?(rt2012|re2020) bâtiment : (.*)$/.exec(raw);
  if (etude) return `${capitalize(etude[2])} — issu d'une étude ${etude[1].toUpperCase()}`;
  return capitalize(raw.replace('à partir des données dpe immeuble', 'à partir du DPE immeuble'));
}

function capitalize(s: string): string {
  return s.charAt(0).toUpperCase() + s.slice(1);
}
