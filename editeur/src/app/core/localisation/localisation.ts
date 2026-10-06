import { textOf } from '../edition/doc-ops';
import { resolvePath } from '../xml/safe-xml';

/**
 * Localisation du bien à partir du XML ADEME.
 *
 * Le géocodage BAN est publié dans `adresse_bien` : `ban_x`/`ban_y` en
 * Lambert 93 (EPSG:2154, documentation XSD). Ils sont convertis en latitude
 * et longitude (RGF93 ≈ WGS84 au mètre près) pour la carte et le fond de plan.
 */
export interface Localisation {
  adresse: string | null;
  lat: number;
  lng: number;
}

const ADRESSE = 'administratif/geolocalisation/adresses/adresse_bien';

export function localisation(doc: Document): Localisation | null {
  const a = resolvePath(doc.documentElement, ADRESSE);
  if (!a) return null;
  const adresse = textOf(a, 'ban_label') ?? textOf(a, 'label_brut_avec_complement') ?? textOf(a, 'label_brut') ?? textOf(a, 'adresse_brut');
  const x = Number(textOf(a, 'ban_x')?.replace(',', '.'));
  const y = Number(textOf(a, 'ban_y')?.replace(',', '.'));
  if (!Number.isFinite(x) || !Number.isFinite(y) || !x || !y) return null;
  const p = lambert93VersWgs84(x, y);
  return Number.isFinite(p.lat) && Number.isFinite(p.lng) ? { adresse, ...p } : null;
}

/** Adresse lisible même sans géocodage (recherche Google Maps par texte). */
export function adresseTexte(doc: Document): string | null {
  const a = resolvePath(doc.documentElement, ADRESSE);
  return a ? (textOf(a, 'ban_label') ?? textOf(a, 'label_brut_avec_complement') ?? textOf(a, 'label_brut')) : null;
}

/**
 * Lambert 93 → latitude/longitude, projection conique conforme inverse
 * (IGN, note NT/G 71, algorithmes ALG0004 et ALG0001), ellipsoïde GRS80.
 */
export function lambert93VersWgs84(x: number, y: number): { lat: number; lng: number } {
  const n = 0.725607765053267;
  const c = 11754255.4260960;
  const xs = 700000;
  const ys = 12655612.0499;
  const e = 0.0818191910428158;
  const lon0 = (3 * Math.PI) / 180;
  const dx = x - xs;
  const dy = y - ys;
  const r = Math.hypot(dx, dy);
  const gamma = Math.atan(dx / -dy);
  const lng = lon0 + gamma / n;
  const latIso = -Math.log(Math.abs(r / c)) / n;
  let phi = 2 * Math.atan(Math.exp(latIso)) - Math.PI / 2;
  for (let i = 0; i < 20; i++) {
    const s = e * Math.sin(phi);
    const next = 2 * Math.atan(Math.pow((1 + s) / (1 - s), e / 2) * Math.exp(latIso)) - Math.PI / 2;
    if (Math.abs(next - phi) < 1e-12) { phi = next; break; }
    phi = next;
  }
  return { lat: (phi * 180) / Math.PI, lng: (lng * 180) / Math.PI };
}

/** Carte intégrable sans clé d'API, vue plan ou satellite. */
export function googleMapsEmbed(l: { lat: number; lng: number }, satellite: boolean, zoom = 19): string {
  return `https://maps.google.com/maps?q=${l.lat.toFixed(6)},${l.lng.toFixed(6)}&z=${zoom}&t=${satellite ? 'k' : 'm'}&output=embed`;
}

export function googleMapsLien(l: { lat: number; lng: number } | null, adresse: string | null): string {
  const q = l ? `${l.lat.toFixed(6)},${l.lng.toFixed(6)}` : (adresse ?? '');
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`;
}

/**
 * Street View intégré au point du bien, sans clé d'API (panorama le plus
 * proche, orienté au nord : on tourne la vue à la souris).
 */
export function googleStreetViewEmbed(l: { lat: number; lng: number }): string {
  return `https://maps.google.com/maps?layer=c&cbll=${l.lat.toFixed(6)},${l.lng.toFixed(6)}&cbp=11,0,0,0,0&output=svembed`;
}

/** Street View en plein écran, par les liens officiels Google Maps (sans clé). */
export function googleStreetViewLien(l: { lat: number; lng: number }): string {
  return `https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=${l.lat.toFixed(6)},${l.lng.toFixed(6)}`;
}

export function googleMapsEmbedAdresse(adresse: string, satellite: boolean): string {
  return `https://maps.google.com/maps?q=${encodeURIComponent(adresse)}&z=18&t=${satellite ? 'k' : 'm'}&output=embed`;
}
