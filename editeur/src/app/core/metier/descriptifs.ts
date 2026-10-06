import type { Dossier } from '../state/dossier';
import { createElement, insertOrdered, objectsOfKind, setLeafText, textOf } from '../edition/doc-ops';
import { schemaPath } from '../schema/schema-registry';
import { childElements, resolvePath } from '../xml/safe-xml';
import { annotate } from '../xml/uid';
import { KIND_BY_KEY } from './catalog';

/**
 * Descriptifs simplifiés (`descriptif_simplifie`), tenus à jour depuis les
 * données saisies.
 *
 * Ce sont les phrases de la page 4 du rapport (« vue d'ensemble du
 * logement ») : une liste par catégorie (murs, planchers, baies et portes,
 * chauffage…), que les logiciels rédigent à partir de leur saisie. Le XSD en
 * exige au moins un. Règle, appliquée après chaque modification :
 *
 *  - catégorie dont la description change avec les données (les phrases
 *    qu'on en tire diffèrent de celles qu'on tirait des données à l'import) :
 *    phrases régénérées ;
 *  - sinon (rien modifié, ou une modification sans effet sur la description,
 *    comme une surface) : textes d'origine, ceux du logiciel, conservés ou
 *    restaurés à leur place. Un fichier importé non modifié reste identique à
 *    l'export, et une modification ne touche jamais une autre catégorie.
 */

/** enum_categorie_descriptif_simplifie_id → types d'objets décrits. */
const CATEGORIES: [string, string[]][] = [
  ['1', ['mur']],
  ['2', ['plancher_bas']],
  ['3', ['plancher_haut']],
  ['4', ['baie_vitree', 'porte']],
  ['5', ['installation_chauffage']],
  ['6', ['installation_ecs']],
  ['7', ['climatisation']],
  ['8', ['ventilation']],
  ['9', ['installation_chauffage']],
  ['10', ['panneaux_pv']],
];

const COLLECTION = 'descriptif_simplifie_collection';

type Lecteur = { val: (el: Element, rel: string) => string | null; lib: (el: Element, rel: string) => string | null };

function lecteur(d: Dossier): Lecteur {
  return {
    val: (el, rel) => textOf(el, rel),
    lib: (el, rel) => {
      const v = textOf(el, rel);
      if (v === null) return null;
      const labels = d.schema?.def(`${schemaPath(el)}/${rel}`)?.enumLabels;
      return labels?.[v] ?? null;
    },
  };
}

const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
const nombre = (v: string | null) => (v === null ? null : Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 2 }));

const ISOLATION: Record<string, string> = {
  '1': 'isolation inconnue', '2': 'non isolé', '3': 'avec isolation intérieure', '4': 'avec isolation extérieure',
  '5': 'avec isolation répartie', '6': 'avec isolation intérieure et extérieure', '7': 'avec isolation intérieure et répartie',
  '8': 'avec isolation extérieure et répartie', '9': 'isolé (type d\'isolation inconnu)',
};

const ADJACENCE: Record<string, string> = {
  '1': 'l\'extérieur', '2': 'le terrain (paroi enterrée)', '3': 'un vide sanitaire', '4': 'un bâtiment ou local à usage autre que d\'habitation',
  '5': 'un terre-plein', '6': 'un sous-sol non chauffé', '7': 'des locaux non chauffés non accessibles', '8': 'un garage', '9': 'un cellier',
  '10': 'un espace tampon solarisé (véranda, loggia fermée)', '11': 'un comble fortement ventilé', '12': 'un comble faiblement ventilé',
  '13': 'un comble très faiblement ventilé', '14': 'des circulations sans ouverture directe sur l\'extérieur',
  '15': 'des circulations avec ouverture directe sur l\'extérieur', '16': 'des circulations avec bouche ou gaine de désenfumage ouverte en permanence',
  '17': 'un hall d\'entrée avec dispositif de fermeture automatique', '18': 'un hall d\'entrée sans dispositif de fermeture automatique',
  '19': 'un garage privé collectif', '20': 'un local tertiaire de l\'immeuble', '21': 'd\'autres dépendances',
};

const SYSTEME: Record<string, string> = {
  '1': 'système individuel', '2': 'système collectif', '3': 'système collectif, réseau de chaleur', '4': 'système hybride collectif-individuel',
};

const INTERMITTENCE: Record<string, string> = {
  '1': 'sans système d\'intermittence', '2': 'programmation centrale sans minimum de température',
  '3': 'programmation centrale avec minimum de température', '4': 'programmation pièce par pièce avec minimum de température',
  '5': 'programmation pièce par pièce avec minimum de température et détection de présence', '6': 'programmation centrale collective',
  '7': 'programmation centrale collective avec détection de présence',
};

/** Isolation et sa précision (résistance, épaisseur ou période), ex. « avec isolation intérieure (R = 3,75 m².K/W) ». */
function isolation(el: Element, l: Lecteur): string {
  const t = l.val(el, 'donnee_entree/enum_type_isolation_id');
  if (!t || !ISOLATION[t]) return '';
  let txt = ' ' + ISOLATION[t];
  if (!['1', '2'].includes(t)) {
    const r = nombre(l.val(el, 'donnee_entree/resistance_isolation'));
    const e = nombre(l.val(el, 'donnee_entree/epaisseur_isolation'));
    const p = l.lib(el, 'donnee_entree/enum_periode_isolation_id');
    if (r) txt += ` (R = ${r} m².K/W)`;
    else if (e) txt += ` (${e} cm d'isolant)`;
    else if (p) txt += ` (isolation ${/^\d/.test(p) ? 'de ' + p : p})`;
  }
  return txt;
}

function adjacence(el: Element, l: Lecteur): string {
  const a = l.val(el, 'donnee_entree/enum_type_adjacence_id');
  if (!a) return '';
  return ' donnant sur ' + (ADJACENCE[a] ?? l.lib(el, 'donnee_entree/enum_type_adjacence_id') ?? '');
}

const nom = (el: Element) => textOf(el, 'donnee_entree/description');

function mur(el: Element, l: Lecteur): string | null {
  const m = l.val(el, 'donnee_entree/enum_materiaux_structure_mur_id');
  const lib = l.lib(el, 'donnee_entree/enum_materiaux_structure_mur_id');
  let txt = m && m !== '1' && lib ? cap(lib.replace(/^murs\b/, 'mur').replace(/matériaux$/, 'matériau')) : 'Mur';
  const e = nombre(l.val(el, 'donnee_entree/epaisseur_structure'));
  if (e) txt += ` d'épaisseur ${e} cm`;
  txt += isolation(el, l) + adjacence(el, l);
  return txt === 'Mur' ? nom(el) : txt;
}

function plancher(el: Element, l: Lecteur, rel: string, defaut: string): string | null {
  const t = l.val(el, rel);
  const lib = l.lib(el, rel);
  const base = t && t !== '1' && lib ? cap(lib) : defaut;
  const txt = base + isolation(el, l) + adjacence(el, l);
  return txt === defaut ? nom(el) : txt;
}

function baie(el: Element, l: Lecteur): string | null {
  const type = l.lib(el, 'donnee_entree/enum_type_baie_id');
  const mat = l.lib(el, 'donnee_entree/enum_type_materiaux_menuiserie_id');
  const ori = l.val(el, 'donnee_entree/enum_orientation_id');
  const vit = l.lib(el, 'donnee_entree/enum_type_vitrage_id');
  const ferm = l.val(el, 'donnee_entree/enum_type_fermeture_id');
  const parts: string[] = [];
  const tete = [type ? cap(type) : 'Baies vitrées', mat].filter(Boolean).join(' ');
  parts.push(tete);
  if (ori === '5') parts.push('en toiture (horizontales)');
  else if (ori) parts.push(`orientées ${cap(l.lib(el, 'donnee_entree/enum_orientation_id') ?? '')}`);
  if (vit) parts.push(vit + (l.val(el, 'donnee_entree/double_fenetre') === '1' ? ', double fenêtre' : ''));
  if (ferm && ferm !== '1') parts.push(`fermeture : ${l.lib(el, 'donnee_entree/enum_type_fermeture_id')}`);
  return parts.length > 1 || type ? parts.join(', ') : nom(el);
}

function porte(el: Element, l: Lecteur): string | null {
  const lib = l.lib(el, 'donnee_entree/enum_type_porte_id');
  if (!lib) return nom(el);
  return 'Porte(s) ' + lib.replace(/^porte simple en /, '').replace(/^toute menuiserie /, '').replace(/ porte /, ' ').replace(/^porte /, '');
}

function enfants(inst: Element, kind: string): Element[] {
  const coll = childElements(inst).find((c) => c.localName === `${kind}_collection`);
  return (coll ? childElements(coll) : []).filter((c) => c.localName === kind);
}

function chauffage(inst: Element, l: Lecteur): string[] {
  const sys = SYSTEME[l.val(inst, 'donnee_entree/enum_type_installation_id') ?? ''];
  const emis = [...new Set(enfants(inst, 'emetteur_chauffage')
    .filter((e) => Number(l.val(e, 'donnee_entree/enum_type_emission_distribution_id')) >= 11)
    .map((e) => l.lib(e, 'donnee_entree/enum_type_emission_distribution_id')).filter((x): x is string => !!x))];
  const out = enfants(inst, 'generateur_chauffage').map((g) => {
    const gen = l.lib(g, 'donnee_entree/enum_type_generateur_ch_id');
    return gen ? cap(gen) + (emis.length ? `, émetteurs : ${emis.join(', ')}` : '') + (sys ? ` (${sys})` : '') : null;
  }).filter((x): x is string => !!x);
  return out.length ? out : [nom(inst)].filter((x): x is string => !!x);
}

function pilotage(inst: Element, l: Lecteur): string[] {
  const gens = enfants(inst, 'generateur_chauffage').map((g) => l.lib(g, 'donnee_entree/enum_type_generateur_ch_id')).filter((x): x is string => !!x);
  const tete = gens.length ? cap(gens[0]) : (nom(inst) ?? 'Chauffage');
  return [...new Set(enfants(inst, 'emetteur_chauffage').map((e) => {
    const parts = [INTERMITTENCE[l.val(e, 'donnee_entree/enum_equipement_intermittence_id') ?? ''], l.lib(e, 'donnee_entree/enum_type_regulation_id')].filter(Boolean);
    return parts.length ? `${tete} : ${parts.join(', ')}` : null;
  }).filter((x): x is string => !!x))];
}

function ecs(inst: Element, l: Lecteur): string[] {
  const sys = SYSTEME[l.val(inst, 'donnee_entree/enum_type_installation_id') ?? ''];
  const out = enfants(inst, 'generateur_ecs').map((g) => {
    const gen = l.lib(g, 'donnee_entree/enum_type_generateur_ecs_id');
    const v = nombre(l.val(g, 'donnee_entree/volume_stockage'));
    return gen ? cap(gen) + (v ? `, contenance ballon ${v} L` : '') + (sys ? ` (${sys})` : '') : null;
  }).filter((x): x is string => !!x);
  return out.length ? out : [nom(inst)].filter((x): x is string => !!x);
}

function climatisation(el: Element, l: Lecteur): string | null {
  const gen = l.lib(el, 'donnee_entree/enum_type_generateur_fr_id');
  const en = l.lib(el, 'donnee_entree/enum_type_energie_id');
  return gen ? cap(gen) + (en ? ` (${en})` : '') : nom(el);
}

function ventilation(el: Element, l: Lecteur): string | null {
  const lib = l.lib(el, 'donnee_entree/enum_type_ventilation_id');
  if (!lib) return nom(el);
  return cap(lib.replace(/\bvmc\b/g, 'VMC').replace(/\bsf\b/g, 'simple flux').replace(/\bdf\b/g, 'double flux')
    .replace(/\bhygro ([ab])\b/g, (_, t: string) => `hygroréglable de type ${t.toUpperCase()}`).replace(/\s+/g, ' '));
}

function photovoltaique(el: Element, l: Lecteur): string | null {
  const s = nombre(l.val(el, 'surface_totale_capteurs'));
  const n = l.val(el, 'nombre_module');
  return 'Panneaux photovoltaïques' + (s ? ` (${s} m² de capteurs)` : n ? ` (${n} modules)` : '');
}

/** Phrases générées pour une catégorie, sans doublon, dans l'ordre des objets. */
export function genererDescriptifs(d: Dossier, categorie: string, doc: Document = d.working): string[] {
  const l = lecteur(d);
  const kinds = CATEGORIES.find(([c]) => c === categorie)?.[1] ?? [];
  const out: (string | null)[] = [];
  for (const k of kinds) {
    for (const el of objectsOfKind(doc, KIND_BY_KEY.get(k)!)) {
      switch (categorie) {
        case '1': out.push(mur(el, l)); break;
        case '2': out.push(plancher(el, l, 'donnee_entree/enum_type_plancher_bas_id', 'Plancher')); break;
        case '3': out.push(plancher(el, l, 'donnee_entree/enum_type_plancher_haut_id', 'Plafond')); break;
        case '4': out.push(k === 'porte' ? porte(el, l) : baie(el, l)); break;
        case '5': out.push(...chauffage(el, l)); break;
        case '6': out.push(...ecs(el, l)); break;
        case '7': out.push(climatisation(el, l)); break;
        case '8': out.push(ventilation(el, l)); break;
        case '9': out.push(...pilotage(el, l)); break;
        case '10': out.push(photovoltaique(el, l)); break;
      }
    }
  }
  return [...new Set(out.filter((x): x is string => !!x && !!x.trim()))];
}

/** Catégories dont la description tirée des données a changé depuis l'import. */
export function categoriesAJour(d: Dossier): Set<string> {
  const out = new Set<string>();
  for (const [c] of CATEGORIES) {
    if (genererDescriptifs(d, c, d.baseline).join('\n') !== genererDescriptifs(d, c).join('\n')) out.add(c);
  }
  return out;
}

/**
 * Met les descriptifs du document de travail en accord avec la règle
 * ci-dessus. À appeler dans la transaction d'une modification. Retourne vrai
 * si la collection a changé.
 */
export function synchroniserDescriptifs(d: Dossier): boolean {
  if (d.format.famille !== 'dpe_logement_existant' || d.format.niveauSupport !== 'complet' || !d.schema) return false;
  if (!d.schema.def(`dpe/${COLLECTION}`)) return false;
  const aRegenerer = categoriesAJour(d);
  const root = d.working.documentElement;
  let coll = resolvePath(root, COLLECTION);
  const existants = coll ? childElements(coll).filter((e) => e.localName === 'descriptif_simplifie') : [];
  const cat = (e: Element) => textOf(e, 'enum_categorie_descriptif_simplifie_id') ?? '';
  const baseColl = resolvePath(d.baseline.documentElement, COLLECTION);
  const origine = (c: string) => (baseColl ? childElements(baseColl) : [])
    .filter((e) => e.localName === 'descriptif_simplifie' && cat(e) === c).map((e) => textOf(e, 'description') ?? '');
  const actuels = (c: string) => existants.filter((e) => cat(e) === c).map((e) => textOf(e, 'description') ?? '');
  // phrases attendues : générées si la description a changé, sinon celles d'origine
  const voulus = new Map(CATEGORIES.map(([c]) => [c, aRegenerer.has(c) ? genererDescriptifs(d, c) : origine(c)] as const)
    .filter(([c, v]) => actuels(c).join('\n') !== v.join('\n')));
  if (!voulus.size) return false;

  if (!coll) {
    coll = createElement(root, COLLECTION);
    insertOrdered(root, coll, d.schema);
    annotate(coll, d.counter, () => true);
  }
  // chaque catégorie régénérée reprend la place de son premier descriptif
  const ancres = new Map<string, Element | null>();
  for (const e of existants) {
    const c = cat(e);
    if (!voulus.has(c)) continue;
    if (!ancres.has(c)) {
      const marque = createElement(coll, 'descriptif_simplifie');
      e.before(marque);
      ancres.set(c, marque);
    }
    e.remove();
  }
  for (const [c, phrases] of [...voulus].sort((a, b) => Number(a[0]) - Number(b[0]))) {
    const marque = ancres.get(c) ?? null;
    for (const p of phrases) {
      const el = createElement(coll, 'descriptif_simplifie');
      const desc = createElement(el, 'description');
      setLeafText(desc, p);
      el.appendChild(desc);
      const ce = createElement(el, 'enum_categorie_descriptif_simplifie_id');
      setLeafText(ce, c);
      el.appendChild(ce);
      if (marque) marque.before(el);
      else coll.appendChild(el);
      annotate(el, d.counter, () => true);
    }
    marque?.remove();
  }
  return true;
}
