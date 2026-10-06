import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { resumeDossier } from '../src/app/core/state/resume';
import { fixture } from './helpers';

describe('résumé des brouillons (accueil)', () => {
  it('lit adresse, type, surface et étiquettes d\'un DPE réel', () => {
    const r = resumeDossier(fixture('2600E0083091A.xml'));
    expect(r.type).toBe('Maison');
    expect(r.adresse).toBeTruthy();
    expect(r.surface).toBeGreaterThan(0);
    expect(r.classeEnergie).toMatch(/^[A-G]$/);
    expect(r.classeClimat).toMatch(/^[A-G]$/);
  });

  it('distingue l\'immeuble, avec son nombre d\'appartements', () => {
    const r = resumeDossier(readFileSync(join(__dirname, '..', '..', 'tests', 'Fixtures', 'comparatif-collectif.xml'), 'utf8'));
    expect(r.type).toBe('Immeuble');
    expect(r.logements).toBe(48);
    expect(r.surface).toBeCloseTo(3780.58, 2);
  });

  it('dossier neuf : décode l\'adresse, sans étiquette', () => {
    const r = resumeDossier('<dpe><administratif><geolocalisation><adresses><adresse_bien><label_brut>12 rue d&apos;Alsace &amp; Lorraine</label_brut></adresse_bien></adresses></geolocalisation></administratif><logement><caracteristique_generale><enum_methode_application_dpe_log_id>11</enum_methode_application_dpe_log_id></caracteristique_generale></logement></dpe>');
    expect(r).toEqual({ adresse: '12 rue d\'Alsace & Lorraine', type: 'Appartement (issu de l\'immeuble)', surface: null, logements: null, classeEnergie: null, classeClimat: null });
  });
});
