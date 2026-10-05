import { Component, computed, inject, input, output, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { KIND_BY_KEY, KindDef, REFS } from '../core/metier/catalog';
import { FicheView, FieldView, GroupView } from '../core/metier/model';
import { isTechnical, themeOf, THEMES } from '../core/metier/field-themes';
import { addGenericItem, addObject, removeGenericItem, removeUnknown, setAttribute } from '../core/edition/editor';
import { FieldComponent } from './field';
import { DeleteDialogComponent } from './delete-dialog';
import { DuplicateDialogComponent } from './duplicate-dialog';

interface Bucket {
  key: string;
  label: string;
  fields: FieldView[];
}

/** Un champ est « visible d'emblée » s'il porte une valeur, est attendu, signalé ou modifié. */
function essential(f: FieldView): boolean {
  return f.state === 'valeur' || f.state === 'nil' || f.modified || f.issues.some((i) => i.gravite !== 'info') || (f.required && f.applicable);
}

@Component({
  selector: 'app-group',
  imports: [FieldComponent],
  template: `
    @let g = group();
    @if (g.result && !top()) {
      <details class="group result" [open]="svc.advanced()">
        <summary>{{ g.label }} <span class="muted">· {{ valued(g).length }} valeur(s)</span> <span class="badge res" [title]="svc.origineResultats()">résultat</span></summary>
        @if (svc.dossier()!.meta.resultatsObsoletes) { <p class="warn-box small">À recalculer : des données d'entrée ont changé.</p> }
        <div class="fields">
          @for (f of valued(g); track f.rel) { <app-field [field]="f" [uid]="uid()" [kindKey]="kindKey()" [readonly]="!svc.advanced()" [rounded]="true" /> }
        </div>
      </details>
    } @else {
      <section class="group" [class.absent]="!g.present">
        @if (!top()) {
          <h4 class="group-title">{{ g.label }}
            @if (g.optional && !g.result && editable()) {
              @if (g.present && g.uid) { <button class="link small danger" (click)="removeBlock(g.uid)">retirer</button> }
              @else if (!g.present) { <button class="link small" (click)="addBlock(g.rel)">+ ajouter ce bloc</button> }
            }
          </h4>
        }
        @if (g.result && top()) { <p class="muted small">Résultats {{ svc.origineResultats() }}, en lecture seule. @if (svc.dossier()!.meta.resultatsObsoletes) { <strong class="warn-text">À recalculer.</strong> }</p> }

        @for (b of buckets(); track b.key) {
          @if (buckets().length > 1) { <div class="theme-title">{{ b.label }}</div> }
          <div class="fields">
            @for (f of b.fields; track f.rel) { <app-field [field]="f" [uid]="uid()" [kindKey]="kindKey()" [readonly]="g.result && !svc.advanced()" [rounded]="g.result" /> }
          </div>
        }
        @if (!buckets().length && !hiddenCount() && !g.repeatables.length && !g.groups.length) { <p class="muted small">Aucune donnée.</p> }

        @if (hiddenCount() > 0) {
          <button class="link small more" (click)="showAll.set(!showAll())">
            {{ showAll() ? 'Masquer les champs vides' : '+ ' + hiddenCount() + ' champ(s) facultatif(s) vide(s)' }}
          </button>
          @if (showAll()) {
            <div class="fields faded">
              @for (f of hidden(); track f.rel) { <app-field [field]="f" [uid]="uid()" [kindKey]="kindKey()" [readonly]="g.result && !svc.advanced()" /> }
            </div>
          }
        }

        @if (technical().length) {
          <details class="tech-details">
            <summary>Détails techniques ({{ technical().length }})</summary>
            <div class="fields">
              @for (f of technical(); track f.rel) { <app-field [field]="f" [uid]="uid()" [kindKey]="kindKey()" [readonly]="g.result && !svc.advanced()" /> }
            </div>
          </details>
        }

        @for (r of g.repeatables; track r.rel) {
          @if (r.items.length || (r.canAdd && !g.result && showAll())) {
            <div class="repeatable">
              <div class="theme-title">{{ r.label }} ({{ r.items.length }})
                @if (r.canAdd && !g.result) { <button class="link small" (click)="addItem(r.rel)">+ ajouter</button> }
              </div>
              @for (it of r.items; track it.uid) {
                <div class="repeat-item">
                  <app-group [group]="it.group" [uid]="it.uid" [kindKey]="null" [top]="true" />
                  @if (!g.result) { <button class="link danger small" (click)="removeItem(it.uid)">retirer</button> }
                </div>
              }
            </div>
          }
        }
        @for (sg of g.groups; track sg.key) {
          @if (sg.present || sg.result || showAll()) { <app-group [group]="sg" [uid]="uid()" [kindKey]="kindKey()" /> }
        }
        @if (g.unknown.length) {
          <div class="unknown">
            <strong>Balises hors schéma</strong> <span class="muted small">— conservées à l'export, mais refusées par la validation XSD</span>
            @for (u of g.unknown; track u.name) {
              <div class="unknown-row">
                <code>{{ u.name }}</code>
                @if (u.rel && editable()) { <button class="link small danger" (click)="removeUnknown(u.rel)">Retirer</button> }
              </div>
              @if (svc.advanced()) { <pre>{{ u.xml }}</pre> }
            }
          </div>
        }
      </section>
    }
  `,
})
export class GroupComponent {
  readonly group = input.required<GroupView>();
  readonly uid = input.required<string>();
  readonly kindKey = input<string | null>(null);
  /** groupe principal de la fiche : pas de titre */
  readonly top = input(false);
  /** champ déjà affiché ailleurs (nom de l'objet dans le titre) */
  readonly hide = input<string | null>(null);
  protected readonly svc = inject(DossierService);
  protected readonly showAll = signal(false);
  protected readonly editable = () => this.svc.dossier()?.format.niveauSupport === 'complet';

  private readonly all = computed(() => [...this.group().fields, ...this.group().notApplicable].filter((f) => f.rel !== this.hide()));
  private readonly flagged = (f: FieldView) => f.issues.some((i) => i.gravite !== 'info');
  protected readonly technical = computed(() => this.all().filter((f) => isTechnical(f.name) && !this.flagged(f) && (f.state === 'valeur' || this.svc.advanced() || this.showAll())));
  protected readonly hidden = computed(() => this.all().filter((f) => !isTechnical(f.name) && !essential(f)));
  protected readonly hiddenCount = computed(() => this.hidden().length);
  protected readonly buckets = computed<Bucket[]>(() => {
    const visible = this.all().filter((f) => (!isTechnical(f.name) || this.flagged(f)) && essential(f));
    if (visible.length <= 6) return visible.length ? [{ key: 'all', label: '', fields: visible }] : [];
    const map = new Map<string, Bucket>();
    for (const f of visible) {
      const t = themeOf(f.name);
      const key = t?.key ?? 'autres';
      if (!map.has(key)) map.set(key, { key, label: t?.label ?? 'Autres caractéristiques', fields: [] });
      map.get(key)!.fields.push(f);
    }
    const order = [...THEMES.map((t) => t.key), 'autres'];
    return [...map.values()].sort((a, b) => order.indexOf(a.key) - order.indexOf(b.key));
  });

  protected valued(g: GroupView): FieldView[] {
    return g.fields.filter((f) => f.state === 'valeur' && !/_depensier$/.test(f.name));
  }

  protected removeUnknown(rel: string): void {
    this.svc.run((d) => removeUnknown(d, this.uid(), rel), 'Balise retirée (annulable par Ctrl+Z).');
  }

  protected addItem(rel: string): void {
    this.svc.run((d) => addGenericItem(d, this.uid(), rel));
  }

  protected addBlock(rel: string): void {
    this.svc.run((d) => addGenericItem(d, this.uid(), rel));
  }

  protected removeBlock(uid: string): void {
    if (confirm('Retirer ce bloc et son contenu ?')) this.svc.run((d) => removeGenericItem(d, uid));
  }

  protected removeItem(uid: string): void {
    if (confirm('Retirer cet élément ?')) this.svc.run((d) => removeGenericItem(d, uid));
  }
}

@Component({
  selector: 'app-fiche',
  imports: [GroupComponent, DeleteDialogComponent, DuplicateDialogComponent],
  template: `
    @let f = fiche();
    @if (!f) {
      <div class="fiche"><p class="muted">Objet introuvable (supprimé ou annulé).</p></div>
    } @else {
      <article class="fiche">
        <header class="fiche-head">
          <div class="fiche-title">
            @if (f.parent) { <a href="" class="crumb" (click)="$event.preventDefault(); goUid(f.parent.uid)">{{ short(f.parent.label) }} ›</a> }
            @if (renaming()) {
              <input class="title-input" [value]="nameValue()" (keydown.enter)="rename($any($event.target).value)" (blur)="rename($any($event.target).value)" (keydown.escape)="renaming.set(false)" />
            } @else {
              <h2 [title]="f.title" [class.editable]="canRename()" (click)="canRename() && startRename()">{{ f.title }} @if (canRename()) { <span class="pencil">✎</span> }</h2>
            }
            <div class="sub">
              <span class="kind">{{ f.kind?.label ?? 'Informations' }}</span>
              @if (view()?.added) { <span class="badge new">ajouté</span> } @else if (view()?.modified) { <span class="badge mod">modifié</span> }
            </div>
          </div>
          <div class="fiche-actions">
            @if (addOptions().length) {
              <div class="dropdown">
                <button (click)="addMenu.set(!addMenu())">+ Ajouter…</button>
                @if (addMenu()) {
                  <div class="menu right" (mouseleave)="addMenu.set(false)">
                    @for (a of addOptions(); track a.label) { <button (click)="addMenu.set(false); a.run()">{{ a.label }}</button> }
                  </div>
                }
              </div>
            }
            @if (f.kind && !f.results && editable()) {
              <div class="dropdown">
                <button class="icon" (click)="moreMenu.set(!moreMenu())" title="Autres actions">⋯</button>
                @if (moreMenu()) {
                  <div class="menu right" (mouseleave)="moreMenu.set(false)">
                    <button (click)="moreMenu.set(false); dupOpen.set(true)">Dupliquer…</button>
                    <button class="danger" (click)="moreMenu.set(false); delOpen.set(true)">Supprimer…</button>
                  </div>
                }
              </div>
            }
            @if (closable()) { <button class="link" (click)="closed.emit()" title="Fermer">✕</button> }
          </div>
        </header>

        @if (attention().length) {
          <div class="attention" [class.err]="attentionErrors() > 0">
            <button class="link" (click)="showIssues.set(!showIssues())">
              {{ attentionErrors() ? '⛔' : '⚠' }} {{ attention().length }} point(s) à vérifier {{ showIssues() ? '▴' : '▾' }}
            </button>
            @if (showIssues()) {
              <ul>
                @for (i of attention(); track i.id) { <li (click)="focusField(i.field)">{{ i.message }}</li> }
              </ul>
            }
          </div>
        }

        @if (svc.advanced()) {
          <div class="tech small">
            <div><span class="muted">Chemin XML :</span> <code>{{ f.xmlPath }}</code></div>
            <div><span class="muted">Identifiant interne :</span> <code>{{ f.uid }}</code> @if (view()?.reference) { · <span class="muted">reference :</span> <code>{{ view()!.reference }}</code> }</div>
          </div>
        }

        @if (f.attributes.length) {
          <section class="group">
            <h4 class="group-title">Attributs du document</h4>
            <div class="fields">
              @for (a of f.attributes; track a.name) {
                <div class="field">
                  <div class="f-label" [title]="a.doc ?? ''">{{ a.name === 'version' ? 'Version du schéma' : 'Attribut ' + a.name }} @if (a.required && a.value === null) { <span class="req">obligatoire</span> }</div>
                  <input type="text" class="inline-input" [value]="a.value ?? ''" (change)="setAttr(a.name, $any($event.target).value)" />
                </div>
              }
            </div>
          </section>
        }

        @if (hasRelations()) {
          <section class="relations">
            @for (c of f.children; track c.kind.key) {
              <div class="rel">
                <div class="rel-label">{{ c.kind.plural }}
                  @if (c.items.length < c.min) { <span class="badge miss">au moins {{ c.min }}</span> }
                </div>
                <div class="rel-items">
                  @for (it of c.items; track it.uid) {
                    <a href="" class="obj-chip" [title]="it.summary" (click)="$event.preventDefault(); goUid(it.uid)"><strong>{{ short(it.title) }}</strong> <span class="muted">{{ firstSummary(it.summary) }}</span></a>
                  }
                  @if (editable()) { <button class="chip-add" (click)="addChild(c.kind)">+ {{ c.kind.label.toLowerCase() }}</button> }
                </div>
              </div>
            }
            @for (r of f.outgoing; track r.label) {
              <div class="rel">
                <div class="rel-label">{{ r.label }}</div>
                <div class="rel-items">
                  @for (it of r.items; track it.label) {
                    @if (it.uid) { <a href="" class="obj-chip" [title]="it.label" (click)="$event.preventDefault(); goUid(it.uid)">↗ {{ short(it.label) }}</a> }
                    @else { <span class="obj-chip muted" [title]="it.label">non décrit dans le dossier</span> }
                  }
                </div>
              </div>
            }
            @for (r of f.incoming; track r.label) {
              <div class="rel">
                <div class="rel-label">{{ r.label }}</div>
                <div class="rel-items">
                  @for (it of r.items; track it.uid) { <a href="" class="obj-chip" [title]="it.label" (click)="$event.preventDefault(); goUid(it.uid)">↙ {{ short(it.label) }}</a> }
                </div>
              </div>
            }
            @for (r of f.roles; track r.label) {
              @if (r.items.length) {
                <div class="rel">
                  <div class="rel-label" [title]="r.doc ?? ''">{{ r.label }}</div>
                  <div class="rel-items">@for (it of r.items; track it.uid) { <a href="" class="obj-chip" (click)="$event.preventDefault(); goUid(it.uid)">{{ short(it.label) }}</a> }</div>
                </div>
              }
            }
          </section>
        }

        @if (f.links.length) {
          <div class="links">@for (l of f.links; track l.label) { <a href="" class="obj-chip" [class.muted]="!l.uid" (click)="$event.preventDefault(); l.uid ? goUid(l.uid) : nav.go(l.tab!, l.section!)">{{ l.label }} ›</a> }</div>
        }

        @for (g of f.groups; track g.key; let i = $index) {
          <app-group [group]="g" [uid]="f.uid" [kindKey]="f.kind?.key ?? null" [top]="g.key === 'donnee_entree' || (i === 0 && !g.result)" [hide]="nameRel()" />
        }
      </article>

      @if (delOpen()) { <app-delete-dialog [uid]="f.uid" (done)="afterDelete($event)" /> }
      @if (dupOpen()) { <app-duplicate-dialog [uid]="f.uid" (done)="afterDuplicate($event)" /> }
    }
  `,
})
export class FicheComponent {
  readonly uid = input.required<string>();
  readonly closed = output<void>();
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly delOpen = signal(false);
  protected readonly dupOpen = signal(false);
  protected readonly addMenu = signal(false);
  protected readonly moreMenu = signal(false);
  protected readonly showIssues = signal(false);
  protected readonly renaming = signal(false);

  /** le nom de l'objet (champ description) s'édite dans le titre */
  protected readonly nameRel = computed(() => this.fiche()?.kind?.nameField ?? null);
  protected readonly canRename = computed(() => !!this.nameRel() && this.editable() && !this.fiche()?.results);
  protected readonly nameValue = computed(() => (this.view()?.name ?? ''));

  protected startRename(): void {
    this.renaming.set(true);
    setTimeout(() => (document.querySelector('.title-input') as HTMLInputElement | null)?.focus());
  }

  protected rename(value: string): void {
    this.renaming.set(false);
    const v = value.trim();
    if (v && v !== this.nameValue()) this.svc.setValue(this.uid(), this.nameRel()!, v);
  }

  protected readonly fiche = computed<FicheView | null>(() => {
    this.svc.revision();
    return this.svc.fiche(this.uid());
  });
  protected readonly view = computed(() => this.svc.model()?.objects.get(this.uid()) ?? null);
  protected readonly editable = computed(() => this.svc.dossier()?.format.niveauSupport === 'complet');
  protected readonly closable = computed(() => this.nav.state().tab === 'controles');
  /** erreurs et avertissements seulement ; les informations restent dans « Contrôles » */
  /** seulement ce qui n'est pas déjà signalé sur un champ visible */
  protected readonly attention = computed(() => {
    const f = this.fiche();
    if (!f) return [];
    const shown = new Set<string>();
    const walk = (g: GroupView) => {
      for (const x of [...g.fields, ...g.notApplicable]) shown.add(x.rel);
      g.groups.forEach(walk);
    };
    f.groups.forEach(walk);
    return f.issues.filter((i) => i.gravite !== 'info' && (!i.field || !shown.has(i.field)));
  });
  protected readonly attentionErrors = computed(() => this.attention().filter((i) => i.gravite === 'erreur').length);
  protected readonly hasRelations = computed(() => {
    const f = this.fiche();
    return !!f && (f.children.length + f.outgoing.length + f.incoming.length + f.roles.filter((r) => r.items.length).length) > 0;
  });

  /** Créations possibles depuis cet objet : objets rattachés (ex. une fenêtre sur un mur). */
  protected readonly addOptions = computed(() => {
    const f = this.fiche();
    if (!f || !this.editable() || f.results || !f.kind) return [];
    const out: { label: string; run: () => void }[] = [];
    for (const r of REFS.filter((x) => x.mode === 'id' && x.toKinds.includes(f.kind!.key) && (x.key === 'paroi' || x.key === 'pt1'))) {
      for (const fk of r.fromKinds) {
        const kind = KIND_BY_KEY.get(fk)!;
        out.push({ label: `${kind.label} sur cet élément`, run: () => this.addLinked(kind, r.key) });
      }
    }
    return out;
  });

  protected short(s: string): string {
    return s.length > 60 ? s.slice(0, 57) + '…' : s;
  }

  protected firstSummary(s: string): string {
    return s.split(' · ').slice(0, 2).join(' · ');
  }

  protected goUid(uid: string): void {
    const m = this.svc.model();
    if (m) this.nav.goObject(m, uid);
  }

  protected focusField(field: string | undefined): void {
    const m = this.svc.model();
    if (m) this.nav.goObject(m, this.uid(), field ?? null);
  }

  protected setAttr(name: string, value: string): void {
    this.svc.run((d) => setAttribute(d, this.uid(), name, value === '' ? null : value));
  }

  protected addChild(kind: KindDef): void {
    const name = kind.nameField ? prompt(`Nom ${kind.article === 'une' ? 'de la' : 'du'} ${kind.label.toLowerCase()} :`, kind.label) : '';
    if (name === null) return;
    const uid = this.svc.run((d) => addObject(d, kind.key, { parentUid: this.uid(), name: name || undefined }), `${kind.label} ajouté(e).`);
    if (uid) this.goUid(uid);
  }

  protected addLinked(kind: KindDef, refKey: string): void {
    const name = kind.nameField ? prompt(`Nom ${kind.article === 'une' ? 'de la' : 'du'} ${kind.label.toLowerCase()} :`, `${kind.label} — ${this.short(this.fiche()!.title)}`) : '';
    if (name === null) return;
    const uid = this.svc.run(
      (d) => addObject(d, kind.key, { name: name || undefined, link: { refKey, targetUid: this.uid() } }),
      `${kind.label} ajouté(e) et rattaché(e).`,
    );
    if (uid) this.goUid(uid);
  }

  protected afterDelete(deleted: boolean): void {
    this.delOpen.set(false);
    if (deleted) this.closed.emit();
  }

  protected afterDuplicate(copyUid: string | null): void {
    this.dupOpen.set(false);
    if (copyUid) this.goUid(copyUid);
  }
}
