import { describe, expect, it } from 'vitest';
import { distinct, findCode, parseMethodes } from '../src/app/core/metier/methode-application';
import { nodeSchemas } from './helpers';

describe('décomposition du type de DPE', () => {
  it('chaque code officiel est atteint par une seule combinaison bien / chauffage / ECS', async () => {
    const schema = (await nodeSchemas().forVersion('2.6'))!;
    const labels = schema.def('dpe/logement/caracteristique_generale/enum_methode_application_dpe_log_id')!.enumLabels!;
    const entries = parseMethodes(labels);
    const keys = entries.map((e) => `${e.bien}|${e.chauffage}|${e.ecs}`);
    expect(new Set(keys).size).toBe(entries.length);
    for (const e of entries) expect(findCode(entries, e.bien, e.chauffage, e.ecs)).toBe(e.code);
    const biens = distinct(entries.map((e) => e.bien));
    expect(biens).toContain('Maison individuelle');
    expect(biens).toContain('Appartement individuel');
    expect(biens).toContain('Immeuble collectif');
    expect(biens.length).toBeLessThan(15);
    expect(entries.find((e) => e.code === '1')).toMatchObject({ bien: 'Maison individuelle', chauffage: null, ecs: null });
    expect(entries.find((e) => e.code === '3')).toMatchObject({ bien: 'Appartement individuel', chauffage: 'Collectif', ecs: 'Individuel' });
    console.log(biens);
  });
});
