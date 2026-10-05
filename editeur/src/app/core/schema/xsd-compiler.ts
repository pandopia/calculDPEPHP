import { AttributeDef, ElementDef, Facets } from './element-def';

const XS = 'http://www.w3.org/2001/XMLSchema';

/**
 * Compile un XSD ADEME en arbre de définitions d'éléments.
 *
 * Constructions supportées — inventaire exhaustif des XSD DPEv1 à DPEv2.6 :
 * xs:element (inline, type= nommé, ref=), xs:complexType (inline ou nommé),
 * xs:all, xs:sequence, xs:choice (imbriqué), xs:any (processContents="skip"),
 * xs:attribute, xs:simpleType/xs:restriction et ses facettes. Toute autre
 * construction lève une erreur plutôt que d'être ignorée en silence.
 *
 * Les libellés d'énumération viennent de l'xs:appinfo JSON de l'élément, à
 * défaut de celui du type nommé : c'est ainsi que l'ADEME les publie.
 */
export class XsdCompiler {
  private namedSimple = new Map<string, Element>();
  private namedComplex = new Map<string, Element>();
  private globalElements = new Map<string, Element>();

  compile(xsdText: string, parser: DOMParser = new DOMParser()): { root: ElementDef; version: string } {
    const doc = parser.parseFromString(xsdText, 'application/xml');
    if (doc.getElementsByTagName('parsererror').length > 0) {
      throw new Error('XSD illisible');
    }
    const schema = doc.documentElement;
    for (const node of xsChildren(schema)) {
      const name = node.getAttribute('name');
      if (!name) continue;
      if (node.localName === 'simpleType') this.namedSimple.set(name, node);
      if (node.localName === 'complexType') this.namedComplex.set(name, node);
      if (node.localName === 'element') this.globalElements.set(name, node);
    }
    const dpe = this.globalElements.get('dpe');
    if (!dpe) throw new Error('Élément racine <dpe> absent du XSD');
    const root = this.compileElement(dpe, '', 0, null);
    return { root, version: documentation(dpe) ?? '' };
  }

  private compileElement(el: Element, parentPath: string, depth: number, parent: ElementDef | null): ElementDef {
    if (depth > 40) throw new Error('Profondeur XSD excessive sous ' + parentPath);
    let decl = el;
    const ref = el.getAttribute('ref');
    if (ref) {
      decl = this.globalElements.get(ref) ?? fail('Référence XSD inconnue : ' + ref);
    }
    const name = decl.getAttribute('name') ?? fail('Élément XSD sans nom');
    const max = el.getAttribute('maxOccurs') ?? '1';
    const def: ElementDef = {
      name,
      path: parentPath === '' ? name : parentPath + '/' + name,
      kind: 'simple',
      minOccurs: el.hasAttribute('minOccurs') ? Number(el.getAttribute('minOccurs')) : 1,
      maxOccurs: max === 'unbounded' ? null : Number(max),
      nillable: decl.getAttribute('nillable') === 'true',
      doc: documentation(el) ?? documentation(decl),
      enumLabels: null,
      typeName: null,
      base: null,
      facets: {},
      compositor: 'all',
      choiceGroup: null,
      order: 0,
      allowsAny: false,
      children: [],
      childMap: new Map(),
      attributes: [],
      parent,
    };
    let labels = appinfoLabels(el) ?? appinfoLabels(decl);
    const type = decl.getAttribute('type') ?? '';
    const inlineComplex = xsChild(decl, 'complexType');
    const inlineSimple = xsChild(decl, 'simpleType');

    if (inlineComplex || this.namedComplex.has(type)) {
      this.compileComplex(def, inlineComplex ?? this.namedComplex.get(type)!, depth);
    } else if (inlineSimple || this.namedSimple.has(type)) {
      const simple = inlineSimple ?? this.namedSimple.get(type)!;
      this.compileSimple(def, simple);
      if (type !== '') {
        def.typeName = type;
        labels = labels ?? appinfoLabels(simple);
      }
    } else if (type.startsWith('xs:')) {
      def.base = type.slice(3);
    } else if (type === '') {
      def.kind = 'any';
    } else {
      fail('Type XSD non résolu : ' + type);
    }
    def.enumLabels = labels;
    return def;
  }

  private compileComplex(def: ElementDef, complex: Element, depth: number): void {
    def.kind = 'complex';
    for (const node of xsChildren(complex)) {
      switch (node.localName) {
        case 'all':
        case 'sequence':
        case 'choice':
          this.compileGroup(def, node, depth, null);
          break;
        case 'attribute': {
          const attr: AttributeDef = {
            name: node.getAttribute('name') ?? '',
            required: node.getAttribute('use') === 'required',
            doc: documentation(node),
          };
          def.attributes.push(attr);
          break;
        }
        case 'annotation':
          break;
        default:
          fail('Construction XSD non supportée : xs:' + node.localName);
      }
    }
  }

  private compileGroup(def: ElementDef, group: Element, depth: number, choiceId: number | null, counter = { n: 0 }): void {
    if (choiceId === null) {
      if (group.localName === 'choice') {
        choiceId = ++counter.n;
      } else {
        def.compositor = group.localName as 'all' | 'sequence';
      }
    }
    for (const node of xsChildren(group)) {
      switch (node.localName) {
        case 'element': {
          const child = this.compileElement(node, def.path, depth + 1, def);
          child.choiceGroup = choiceId;
          child.order = def.children.length;
          def.children.push(child);
          def.childMap.set(child.name, child);
          break;
        }
        case 'choice':
          this.compileGroup(def, node, depth, ++counter.n, counter);
          break;
        case 'sequence':
          this.compileGroup(def, node, depth, choiceId, counter);
          break;
        case 'any':
          def.allowsAny = true;
          break;
        case 'annotation':
          break;
        default:
          fail('Construction XSD non supportée : xs:' + node.localName);
      }
    }
  }

  private compileSimple(def: ElementDef, simple: Element): void {
    def.kind = 'simple';
    const restriction = xsChild(simple, 'restriction') ?? fail('simpleType sans restriction sous ' + def.path);
    const base = restriction.getAttribute('base') ?? '';
    const named = this.namedSimple.get(base);
    if (named) {
      this.compileSimple(def, named);
    } else {
      def.base = base.startsWith('xs:') ? base.slice(3) : base;
    }
    for (const facet of xsChildren(restriction)) {
      const value = facet.getAttribute('value') ?? '';
      const name = facet.localName as keyof Facets;
      switch (facet.localName) {
        case 'enumeration':
        case 'pattern':
          (def.facets[name] as string[] | undefined) ??= [];
          (def.facets[name] as string[]).push(value);
          break;
        case 'minInclusive':
        case 'maxInclusive':
        case 'minExclusive':
        case 'maxExclusive':
        case 'length':
        case 'minLength':
        case 'maxLength':
        case 'totalDigits':
        case 'fractionDigits':
          (def.facets as Record<string, string>)[name] = value;
          break;
        case 'annotation':
          break;
        default:
          fail('Facette XSD non supportée : xs:' + facet.localName);
      }
    }
  }
}

function fail(message: string): never {
  throw new Error(message);
}

function xsChildren(el: Element): Element[] {
  const out: Element[] = [];
  for (let n = el.firstElementChild; n; n = n.nextElementSibling) {
    if (n.namespaceURI === XS) out.push(n);
  }
  return out;
}

function xsChild(el: Element, localName: string): Element | null {
  return xsChildren(el).find((n) => n.localName === localName) ?? null;
}

function documentation(el: Element): string | null {
  const annotation = xsChild(el, 'annotation');
  const doc = annotation ? xsChild(annotation, 'documentation') : null;
  const text = (doc?.textContent ?? '').replace(/\s+/g, ' ').trim();
  return text === '' ? null : text;
}

function appinfoLabels(el: Element): Record<string, string> | null {
  const annotation = xsChild(el, 'annotation');
  const appinfo = annotation ? xsChild(annotation, 'appinfo') : null;
  const text = (appinfo?.textContent ?? '').trim();
  if (!text.startsWith('{')) return null;
  try {
    const decoded = JSON.parse(text) as Record<string, unknown>;
    const labels: Record<string, string> = {};
    for (const [code, label] of Object.entries(decoded)) {
      labels[code] = typeof label === 'string' ? label : JSON.stringify(label);
    }
    return labels;
  } catch {
    return null;
  }
}
