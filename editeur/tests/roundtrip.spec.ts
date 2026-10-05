import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { parseXml } from '../src/app/core/xml/safe-xml';
import { annotate, UidCounter } from '../src/app/core/xml/uid';
import { exportXml, serialize } from '../src/app/core/xml/serializer';
import { semanticDiff } from '../src/app/core/xml/semantic-compare';
import { fixture, localCorpus } from './helpers';

function roundTrip(text: string): string[] {
  const original = parseXml(text);
  const working = parseXml(text);
  annotate(working.documentElement, new UidCounter());
  // persistance du brouillon (uid conservés) puis rechargement
  const draft = parseXml(serialize(working, { keepUids: true }).text);
  const exported = exportXml(draft);
  expect(exported).not.toContain('urn:calculdpe:editeur');
  return semanticDiff(original, parseXml(exported));
}

describe('aller-retour XML sans modification', () => {
  for (const name of ['2400E0077027S.xml', '2600E0083091A.xml', '2400E0020849A.xml', '2400E0571888V.xml']) {
    it(`préserve le contenu de ${name}`, () => {
      expect(roundTrip(fixture(name))).toEqual([]);
    });
  }

  it('préserve attributs, espaces de noms, commentaires, CDATA et xsi:nil', () => {
    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<!-- avant -->
<dpe xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:x="urn:ext" version="2" id="a&amp;b">
  <administratif><dpe_a_remplacer xsi:nil="true"/><x:extension x:attr="1">t</x:extension></administratif>
  <logement><vide/><texte>  espaces  </texte><cdata><![CDATA[<brut>]]></cdata><!-- c --><mixte>a<b>b</b>c</mixte></logement>
</dpe>`;
    expect(roundTrip(xml)).toEqual([]);
  });

  it('export déterministe', () => {
    const doc = parseXml(fixture('2400E0077027S.xml'));
    expect(exportXml(doc)).toBe(exportXml(parseXml(exportXml(doc))));
  });

  it.runIf(process.env['CORPUS'] === '1')('corpus local complet (CORPUS=1)', () => {
    const files = localCorpus();
    const failures: string[] = [];
    for (const f of files) {
      const d = roundTrip(readFileSync(f, 'utf8'));
      if (d.length) failures.push(f + ' : ' + d[0]);
    }
    expect(failures).toEqual([]);
  });
});

describe('chargement sécurisé', () => {
  it('refuse DOCTYPE / entités', () => {
    expect(() => parseXml('<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><dpe>&e;</dpe>')).toThrow(/DOCTYPE/);
  });
  it('refuse un XML mal formé', () => {
    expect(() => parseXml('<dpe><a></dpe>')).toThrow(/mal formé/);
  });
  it('refuse une profondeur excessive', () => {
    expect(() => parseXml('<a>'.repeat(70) + '</a>'.repeat(70))).toThrow(/Profondeur/);
  });
});
