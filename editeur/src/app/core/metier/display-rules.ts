/**
 * Règles d'affichage et d'obligation conditionnelles, toutes sourcées.
 *
 * Une règle déclare qu'un ou plusieurs champs ne sont pertinents que pour
 * certains codes d'un champ de contrôle. Conséquences :
 *  - champ non pertinent et vide → masqué (« champs non applicables ») ;
 *  - champ non pertinent mais renseigné → affiché, signalé, jamais effacé ;
 *  - champ pertinent et `required` absent → avertissement « attendu ».
 * Si le champ de contrôle est vide, rien n'est masqué.
 */
export interface DisplayRule {
  id: string;
  kinds: string[];
  /** chemin relatif du champ de contrôle */
  control: string;
  codes: string[];
  /** chemins relatifs gouvernés */
  fields: string[];
  required: boolean;
  source: string;
}

const PAROIS_OPAQUES = ['mur', 'plancher_bas', 'plancher_haut'];
const U = 'donnee_entree/enum_methode_saisie_u_id';
const U0 = 'donnee_entree/enum_methode_saisie_u0_id';
const SRC_U = 'Libellés XSD de enum_methode_saisie_u_id (méthode de saisie du U)';
const SRC_U0 = 'Libellés XSD de enum_methode_saisie_u0_id';
const SRC_VITRAGE = 'Libellés XSD de enum_methode_saisie_perf_vitrage_id (grandeurs « saisies directement »)';
const LNC_CODES = ['8', '9', '11', '12', '13', '14', '15', '16', '17', '18', '19', '21'];
const SRC_LNC =
  'Méthode 3CL-2021 §3.1 p.8-12 : Aiu et Aue servent au calcul de b pour les locaux non chauffés (garage, cellier, combles, circulations, halls…) ; b est forfaitaire pour l\'extérieur, le terre-plein, le vide sanitaire, le sous-sol, les locaux non accessibles et les locaux d\'un autre usage.';

function uFields(kind: string): { u: string; u0: string; tvu0: string } {
  const x = kind === 'mur' ? 'umur' : kind === 'plancher_bas' ? 'upb' : 'uph';
  return { u: `donnee_entree/${x}_saisi`, u0: `donnee_entree/${x}0_saisi`, tvu0: `donnee_entree/tv_${x}0_id` };
}

export const DISPLAY_RULES: DisplayRule[] = [
  ...PAROIS_OPAQUES.flatMap((kind): DisplayRule[] => {
    const f = uFields(kind);
    return [
      { id: `${kind}-u-epaisseur`, kinds: [kind], control: U, codes: ['3', '4'], fields: ['donnee_entree/epaisseur_isolation'], required: true, source: SRC_U },
      { id: `${kind}-u-resistance`, kinds: [kind], control: U, codes: ['5', '6'], fields: ['donnee_entree/resistance_isolation'], required: true, source: SRC_U },
      { id: `${kind}-u-periode`, kinds: [kind], control: U, codes: ['7'], fields: ['donnee_entree/enum_periode_isolation_id'], required: true, source: SRC_U },
      { id: `${kind}-u-direct`, kinds: [kind], control: U, codes: ['9', '10'], fields: [f.u], required: true, source: SRC_U },
      { id: `${kind}-u0-direct`, kinds: [kind], control: U0, codes: ['3', '4'], fields: [f.u0], required: true, source: SRC_U0 },
      { id: `${kind}-u0-table`, kinds: [kind], control: U0, codes: ['2'], fields: [f.tvu0], required: false, source: SRC_U0 },
    ];
  }),
  { id: 'porte-u', kinds: ['porte'], control: 'donnee_entree/enum_methode_saisie_uporte_id', codes: ['2', '3'], fields: ['donnee_entree/uporte_saisi'], required: true, source: 'Libellés XSD de enum_methode_saisie_uporte_id' },
  { id: 'pt-k', kinds: ['pont_thermique'], control: 'donnee_entree/enum_methode_saisie_pont_thermique_id', codes: ['2', '3'], fields: ['donnee_entree/k_saisi'], required: true, source: 'Libellés XSD de enum_methode_saisie_pont_thermique_id' },
  { id: 'baie-ug', kinds: ['baie_vitree'], control: 'donnee_entree/enum_methode_saisie_perf_vitrage_id', codes: ['2', '3', '4', '5', '6', '15'], fields: ['donnee_entree/ug_saisi'], required: true, source: SRC_VITRAGE },
  { id: 'baie-uw', kinds: ['baie_vitree'], control: 'donnee_entree/enum_methode_saisie_perf_vitrage_id', codes: ['3', '4', '5', '6', '8', '9', '10', '13'], fields: ['donnee_entree/uw_saisi'], required: true, source: SRC_VITRAGE },
  { id: 'baie-sw', kinds: ['baie_vitree'], control: 'donnee_entree/enum_methode_saisie_perf_vitrage_id', codes: ['4', '6', '8', '10', '11', '14', '15'], fields: ['donnee_entree/sw_saisi'], required: true, source: SRC_VITRAGE },
  { id: 'baie-ujn', kinds: ['baie_vitree'], control: 'donnee_entree/enum_methode_saisie_perf_vitrage_id', codes: ['5', '6', '9', '10', '11', '12'], fields: ['donnee_entree/ujn_saisi'], required: true, source: SRC_VITRAGE },
  { id: 'ventil-q4pa', kinds: ['ventilation'], control: 'donnee_entree/enum_methode_saisie_q4pa_conv_id', codes: ['2'], fields: ['donnee_entree/q4pa_conv_saisi'], required: true, source: 'Libellés XSD de enum_methode_saisie_q4pa_conv_id' },
  { id: 'ch-fch', kinds: ['installation_chauffage'], control: 'donnee_entree/enum_methode_saisie_fact_couv_sol_id', codes: ['2'], fields: ['donnee_entree/fch_saisi'], required: true, source: 'Libellés XSD de enum_methode_saisie_fact_couv_sol_id' },
  { id: 'ecs-fecs', kinds: ['installation_ecs'], control: 'donnee_entree/enum_methode_saisie_fact_couv_sol_id', codes: ['2'], fields: ['donnee_entree/fecs_saisi'], required: true, source: 'Libellés XSD de enum_methode_saisie_fact_couv_sol_id' },
  {
    id: 'lnc-surfaces', kinds: ['mur', 'plancher_bas', 'plancher_haut', 'baie_vitree', 'porte'], control: 'donnee_entree/enum_type_adjacence_id',
    codes: LNC_CODES, fields: ['donnee_entree/surface_aiu', 'donnee_entree/surface_aue'], required: true, source: SRC_LNC,
  },
  {
    id: 'lnc-cfg', kinds: ['mur', 'plancher_bas', 'plancher_haut', 'baie_vitree', 'porte'], control: 'donnee_entree/enum_type_adjacence_id',
    codes: [...LNC_CODES, '10'], fields: ['donnee_entree/enum_cfg_isolation_lnc_id'], required: false, source: SRC_LNC,
  },
];

export interface Applicability {
  applicable: boolean;
  required: boolean;
  rules: DisplayRule[];
}

/** Pertinence d'un champ, selon les valeurs de contrôle lues par `value`. */
export function applicability(kind: string | undefined, field: string, value: (rel: string) => string | null): Applicability {
  const rules = DISPLAY_RULES.filter((r) => kind && r.kinds.includes(kind) && r.fields.includes(field));
  if (rules.length === 0) return { applicable: true, required: false, rules };
  let applicable = false;
  let required = false;
  let anyControlKnown = false;
  for (const r of rules) {
    const v = value(r.control);
    if (v === null) continue;
    anyControlKnown = true;
    if (r.codes.includes(v)) {
      applicable = true;
      required ||= r.required;
    }
  }
  return { applicable: applicable || !anyControlKnown, required, rules };
}
