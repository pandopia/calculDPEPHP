import { Dossier } from '../state/dossier';
import { ensurePath, setLeafText } from '../edition/doc-ops';

/**
 * Géocodage de l'adresse du bien par la Base Adresse Nationale
 * (api-adresse.data.gouv.fr, service public sans clé). C'est le référentiel
 * attendu par le XSD ADEME : `ban_id`, `ban_label`, `ban_x`/`ban_y` en
 * Lambert 93, `ban_score`… sont les propriétés renvoyées par cette API.
 */
export const BAN_URL = 'https://api-adresse.data.gouv.fr/search/';

export interface AdresseBan {
  label: string;
  name: string;
  housenumber: string | null;
  street: string | null;
  postcode: string;
  citycode: string;
  city: string;
  type: string;
  score: number;
  id: string;
  banId: string | null;
  x: number;
  y: number;
  /** contexte département, région (affichage) */
  context: string;
}

export async function rechercherAdresses(q: string, fetcher: typeof fetch = (...a) => fetch(...a)): Promise<AdresseBan[]> {
  const texte = q.trim();
  if (texte.length < 3) return [];
  const res = await fetcher(`${BAN_URL}?q=${encodeURIComponent(texte)}&limit=6&autocomplete=1`);
  if (!res.ok) throw new Error(`Base Adresse Nationale indisponible (HTTP ${res.status}).`);
  const json = (await res.json()) as { features?: { properties?: Record<string, unknown> }[] };
  return (json.features ?? []).map((f) => f.properties ?? {}).filter((p) => typeof p['label'] === 'string' && Number.isFinite(Number(p['x']))).map((p) => ({
    label: String(p['label']),
    name: String(p['name'] ?? p['label']),
    housenumber: p['housenumber'] ? String(p['housenumber']) : null,
    street: p['street'] ? String(p['street']) : null,
    postcode: String(p['postcode'] ?? ''),
    citycode: String(p['citycode'] ?? ''),
    city: String(p['city'] ?? ''),
    type: String(p['type'] ?? ''),
    score: Number(p['score'] ?? 0),
    id: String(p['id'] ?? ''),
    banId: p['banId'] ? String(p['banId']) : null,
    x: Number(p['x']),
    y: Number(p['y']),
    context: String(p['context'] ?? ''),
  }));
}

const ADRESSE = 'administratif/geolocalisation/adresses/adresse_bien';

/**
 * Écrit l'adresse choisie dans `adresse_bien` (champs « brut » et champs BAN),
 * en une seule modification annulable. Les compléments (bâtiment, étage,
 * logement…) déjà saisis sont conservés.
 */
export function appliquerAdresse(d: Dossier, a: AdresseBan, aujourdhui = new Date()): void {
  d.transact('Adresse du bien (Base Adresse Nationale)', () => {
    const bloc = ensurePath(d.working.documentElement, ADRESSE, d.schema, d.counter);
    const valeurs: [string, string | null][] = [
      ['adresse_brut', a.name],
      ['code_postal_brut', a.postcode],
      ['nom_commune_brut', a.city],
      ['label_brut', a.label],
      ['enum_statut_geocodage_ban_id', '1'],
      ['ban_date_appel', aujourdhui.toISOString().slice(0, 10)],
      ['ban_id', a.id],
      ['ban_id_ban_adresse', a.banId],
      ['ban_label', a.label],
      ['ban_housenumber', a.housenumber],
      ['ban_street', a.street ?? (a.type === 'street' ? a.name : null)],
      ['ban_citycode', a.citycode],
      ['ban_postcode', a.postcode],
      ['ban_city', a.city],
      ['ban_type', a.type],
      ['ban_score', String(a.score)],
      ['ban_x', String(a.x)],
      ['ban_y', String(a.y)],
    ];
    for (const [champ, v] of valeurs) {
      const existant = Array.from(bloc.children).find((c) => c.localName === champ);
      if (v === null || v === '') { existant?.remove(); continue; }
      setLeafText(existant ?? ensurePath(bloc, champ, d.schema, d.counter), v);
    }
    // libellé avec complément : repris du libellé si absent (le complément reste à saisir)
    if (!Array.from(bloc.children).some((c) => c.localName === 'label_brut_avec_complement' && c.textContent?.trim())) {
      setLeafText(ensurePath(bloc, 'label_brut_avec_complement', d.schema, d.counter), a.label);
    }
    return { result: undefined, affectsResults: [] };
  });
}
