import { describe, expect, it } from 'vitest';
import { existsSync, readFileSync } from 'node:fs';
import { Dossier } from '../src/app/core/state/dossier';
import { buildMaquette, FACADES } from '../src/app/core/metier/maquette';
import { fixture, nodeSchemas } from './helpers';

const USER = '/Users/cyrilbele/Desktop/DPE_605CR_20251223_168555.xml';

describe('maquette volumique approximative', () => {
  for (const name of ['2600E0083091A.xml', '2400E0020849A.xml', '2400E0571888V.xml']) {
    it(`reste cohérente pour ${name}`, async () => {
      const d = await Dossier.fromImport(fixture(name), name, nodeSchemas());
      const m = buildMaquette(d)!;
      expect(m.width).toBeGreaterThan(0);
      expect(m.depth).toBeGreaterThan(0);
      expect(m.height).toBeGreaterThan(0);
      for (const f of FACADES) {
        const side = f === 'sud' || f === 'nord' ? m.width : m.depth;
        const used = m.facades[f].reduce((n, s) => n + s.length, 0);
        if (m.facades[f].length) expect(used).toBeCloseTo(side, 5);
      }
      for (const o of m.openings) {
        expect(o.w).toBeGreaterThan(0);
        if (o.facade !== 'toit') {
          const side = o.facade === 'sud' || o.facade === 'nord' ? m.width : m.depth;
          expect(o.x).toBeGreaterThanOrEqual(-1e-6);
          expect(o.x + o.w).toBeLessThanOrEqual(side + 1e-6);
          expect(o.y + o.h).toBeLessThanOrEqual(m.height + 1e-6);
        }
      }
    });
  }

  it.runIf(existsSync(USER))('fichier utilisateur (local)', async () => {
    const d = await Dossier.fromImport(readFileSync(USER, 'utf8'), 'u.xml', nodeSchemas());
    const m = buildMaquette(d)!;
    console.log({ w: m.width, d: m.depth, h: m.height, levels: m.levels, scope: m.scope, openings: m.openings.length, notes: m.notes, facades: Object.fromEntries(FACADES.map((f) => [f, m.facades[f].length])) });
  });
});
