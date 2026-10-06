import { describe, expect, it } from 'vitest';
import { googleMapsEmbed, googleMapsLien, lambert93VersWgs84, localisation } from '../src/app/core/localisation/localisation';
import { appliquerAdresse, rechercherAdresses } from '../src/app/core/localisation/geocodage';
import { compacterPlan } from '../src/app/core/localisation/plan-compact';
import { Dossier } from '../src/app/core/state/dossier';
import { buildSkeleton } from '../src/app/core/state/skeleton';
import { buildModel } from '../src/app/core/metier/model';
import { nodeSchemas } from './helpers';

describe('localisation du bien', () => {
  it('convertit les coordonnées BAN Lambert 93 en latitude/longitude', () => {
    // point de référence de la projection : origine (3° E, 46,5° N)
    const o = lambert93VersWgs84(700000, 6600000);
    expect(o.lng).toBeCloseTo(3, 6);
    expect(o.lat).toBeCloseTo(46.5, 6);
    // 3 rue Juiverie, Lyon 5e : la BAN publie x/y Lambert 93 et lon/lat du même point
    const lyon = lambert93VersWgs84(842011.59, 6520084.79);
    expect(lyon.lat).toBeCloseTo(45.765581, 5);
    expect(lyon.lng).toBeCloseTo(4.827322, 5);
  });

  it('construit les liens Google Maps sans clé d\'API', () => {
    const p = { lat: 45.76468, lng: 4.82771 };
    expect(googleMapsEmbed(p, true)).toBe('https://maps.google.com/maps?q=45.764680,4.827710&z=19&t=k&output=embed');
    expect(googleMapsLien(null, '3 Rue Juiverie 69005 Lyon')).toContain('query=3%20Rue%20Juiverie');
  });
});

describe('géocodage par la Base Adresse Nationale', () => {
  // réponse réelle de api-adresse.data.gouv.fr (enregistrée, pas d'appel réseau en test)
  const BAN = { features: [{ properties: { label: '3 Rue Juiverie 69005 Lyon', score: 0.97, housenumber: '3', id: '69385_3940_00003', banId: 'b1c2', name: '3 Rue Juiverie', postcode: '69005', citycode: '69385', x: 842011.59, y: 6520084.79, city: 'Lyon', context: '69, Rhône, Auvergne-Rhône-Alpes', type: 'housenumber', street: 'Rue Juiverie' } }] };

  it('interroge la BAN et lit ses propriétés', async () => {
    let url = '';
    const r = await rechercherAdresses('3 rue juiverie lyon', (async (u: string) => { url = u; return new Response(JSON.stringify(BAN)); }) as unknown as typeof fetch);
    expect(url).toBe('https://api-adresse.data.gouv.fr/search/?q=3%20rue%20juiverie%20lyon&limit=6&autocomplete=1');
    expect(r[0]).toMatchObject({ label: '3 Rue Juiverie 69005 Lyon', x: 842011.59, citycode: '69385', banId: 'b1c2' });
    expect(await rechercherAdresses('3 ')).toEqual([]);
  });

  it('remplit l\'adresse d\'un dossier neuf, valide au XSD, et la rend localisable', async () => {
    const schemas = nodeSchemas();
    const schema = (await schemas.forVersion('2.6'))!;
    const d = await Dossier.fromSkeleton(buildSkeleton(schema, { version: '2.6', methodeApplication: '1' }), 'Neuf', schemas);
    expect(localisation(d.working)).toBeNull();
    const [a] = await rechercherAdresses('3 rue juiverie lyon', (async () => new Response(JSON.stringify(BAN))) as unknown as typeof fetch);
    appliquerAdresse(d, a, new Date('2026-10-06'));
    const l = localisation(d.working)!;
    expect(l.adresse).toBe('3 Rue Juiverie 69005 Lyon');
    expect(l.lat).toBeCloseTo(45.765581, 5);
    const xml = d.exportXml();
    expect(xml).toContain('<ban_date_appel>2026-10-06</ban_date_appel>');
    expect(xml).toContain('<enum_statut_geocodage_ban_id>1</enum_statut_geocodage_ban_id>');
    // ordre du schéma respecté : adresse_brut avant ban_id, ban_x avant ban_y
    expect(xml.indexOf('<adresse_brut>')).toBeLessThan(xml.indexOf('<ban_id>'));
    expect(xml.indexOf('<ban_x>')).toBeLessThan(xml.indexOf('<ban_y>'));
    expect(buildModel(d).issues.filter((i) => i.field?.startsWith('ban_') && i.gravite === 'erreur')).toEqual([]);
    d.undo();
    expect(localisation(d.working)).toBeNull();
  });
});

describe('allègement du plan sauvegardé', () => {
  it('ré-encode une seule fois chaque grande image PNG et laisse le reste intact', async () => {
    const png = 'data:image/png;base64,' + 'A'.repeat(300_000);
    const petit = 'data:image/png;base64,AAAA';
    const projet = { version: '5.3.0', objects: [{ type: 'image', src: png, width: 2560 }], floors: [{ objects: [{ src: png }, { src: petit }] }], meta: { name: 'x' } };
    let appels = 0;
    const cache = new Map<string, string>();
    const out = await compacterPlan(projet, async () => { appels++; return 'data:image/jpeg;base64,BBBB'; }, cache);
    expect(appels).toBe(1);
    expect(out.objects[0]).toEqual({ type: 'image', src: 'data:image/jpeg;base64,BBBB', width: 2560 });
    expect(out.floors[0].objects[1].src).toBe(petit);
    expect(projet.objects[0].src).toBe(png); // l'objet émis par l'éditeur n'est pas modifié
    await compacterPlan(projet, async () => { appels++; return null; }, cache);
    expect(appels).toBe(1);
    // transparence (encodeur → null) : PNG conservé
    expect((await compacterPlan(projet, async () => null)).objects[0].src).toBe(png);
  });
});
