/**
 * Allègement d'un projet g-plan avant sa sauvegarde dans le brouillon.
 *
 * Le fond de carte capturé par g-plan est une image PNG en base64 (≈ 7 Mo pour
 * 2560 × 1920 px), présente deux fois dans le projet (niveau courant et liste
 * des niveaux) et réécrite à chaque modification. Ré-encodée en JPEG aux mêmes
 * dimensions, elle se place exactement pareil et pèse environ dix fois moins.
 * Une image comportant de la transparence est laissée en PNG.
 */
export type EncodeurImage = (dataUrl: string) => Promise<string | null>;

/** Au-delà de cette taille, une image PNG embarquée est candidate au JPEG. */
const SEUIL = 200_000;

export async function compacterPlan<T>(projet: T, encoder: EncodeurImage, cache = new Map<string, string>()): Promise<T> {
  const cles = new Set<string>();
  const collecter = (v: unknown): void => {
    if (typeof v === 'string') { if (v.length > SEUIL && v.startsWith('data:image/png')) cles.add(v); return; }
    if (v && typeof v === 'object') for (const x of Object.values(v)) collecter(x);
  };
  collecter(projet);
  for (const png of cles) {
    if (cache.has(png)) continue;
    cache.set(png, (await encoder(png).catch(() => null)) ?? png);
  }
  const remplacer = (v: unknown): unknown => {
    if (typeof v === 'string') return cache.get(v) ?? v;
    if (Array.isArray(v)) return v.map(remplacer);
    if (v && typeof v === 'object') return Object.fromEntries(Object.entries(v).map(([k, x]) => [k, remplacer(x)]));
    return v;
  };
  return remplacer(projet) as T;
}

/** Encodeur navigateur : JPEG qualité 0,82, ou null si l'image a de la transparence. */
export const encodeurJpeg: EncodeurImage = (dataUrl) =>
  new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      const c = document.createElement('canvas');
      c.width = img.naturalWidth;
      c.height = img.naturalHeight;
      const ctx = c.getContext('2d');
      if (!ctx) return resolve(null);
      ctx.drawImage(img, 0, 0);
      const px = ctx.getImageData(0, 0, c.width, c.height).data;
      for (let i = 3; i < px.length; i += 4) if (px[i] < 255) return resolve(null);
      const jpeg = c.toDataURL('image/jpeg', 0.82);
      resolve(jpeg.length < dataUrl.length ? jpeg : null);
    };
    img.onerror = () => resolve(null);
    img.src = dataUrl;
  });
