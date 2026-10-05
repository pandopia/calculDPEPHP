import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { SchemaRegistry } from '../src/app/core/schema/schema-registry';

export const SCHEMAS_DIR = join(__dirname, '..', 'public', 'schemas');
export const FIXTURES_DIR = join(__dirname, 'fixtures');

export function fixture(name: string): string {
  return readFileSync(join(FIXTURES_DIR, name), 'utf8');
}

export function nodeSchemas(): SchemaRegistry {
  return new SchemaRegistry(async (file) => readFileSync(join(SCHEMAS_DIR, file), 'utf8'));
}

/** Corpus local complet (non versionné) : utilisé s'il est présent. */
export function localCorpus(): string[] {
  const dirs = [
    join(__dirname, '..', '..', 'resources', 'XML', 'official', 'ademe-2026-09', 'expected'),
    join(__dirname, '..', '..', 'resources', 'XML', 'verif'),
  ];
  return dirs.filter(existsSync).flatMap((d) => readdirSync(d).filter((f) => f.endsWith('.xml')).map((f) => join(d, f)));
}
