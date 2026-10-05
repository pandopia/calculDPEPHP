import { Component, computed, inject, input, output, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { KIND_BY_KEY, KindDef, REFS } from '../core/metier/catalog';
import { FicheView, GroupView } from '../core/metier/model';
import { addGenericItem, addObject, removeGenericItem, setAttribute } from '../core/edition/editor';
import { FieldComponent } from './field';
import { DeleteDialogComponent } from './delete-dialog';
import { DuplicateDialogComponent } from './duplicate-dialog';
import { GRAVITE_LABELS } from './labels';
import { NIVEAU_LABELS } from '../core/validation/issues';

@Component({
  selector: 'app-group',
  imports: [FieldComponent],
  template: `
    @let g = group();
    <fieldset class="group" [class.result]="g.result" [class.absent]="!g.present">
      <legend>
        {{ g.label }}
        @if (g.result) { <span class="badge res" title="Valeurs écrites par le logiciel d'origine : non recalculées ici">Résultat du fichier source</span> }
        @if (!g.present) { <span class="muted small">— absent du fichier</span> }
        @if (g.optional && !g.result && editable()) {
          @if (g.present && g.uid) { <button class="link small danger" (click)="removeBlock(g.uid)">Retirer ce bloc</button> }
          @else if (!g.present) { <button class="link small" (click)="addBlock(g.rel)">Ajouter ce bloc</button> }
        }
      </legend>
      @if (g.result && svc.dossier()!.meta.resultatsObsoletes && g.present) { <p class="warn-box small">À recalculer : des données d'entrée ont changé.</p> }
      @if (g.result && !svc.advanced() && g.present) { <p class="muted small">Lecture seule. Le mode avancé permet de corriger une valeur saisie que le format range ici (puissance nominale, etc.).</p> }
      <div class="fields">
        @for (f of g.fields; track f.rel) { <app-field [field]="f" [uid]="uid()" [kindKey]="kindKey()" [readonly]="g.result && !svc.advanced()" /> }
      </div>
      @if (g.notApplicable.length) {
        <details class="na">
          <summary>{{ g.notApplicable.length }} champ(s) non applicable(s) pour les choix actuels</summary>
          <p class="muted small">Masqués car non pertinents selon une règle documentée ({{ g.notApplicable[0].applicabilitySource }}). Ils restent saisissables.</p>
          <div class="fields">
            @for (f of g.notApplicable; track f.rel) { <app-field [field]="f" [uid]="uid()" [kindKey]="kindKey()" [readonly]="g.result && !svc.advanced()" /> }
          </div>
        </details>
      }
      @for (r of g.repeatables; track r.rel) {
        <div class="repeatable">
          <h4>{{ r.label }} <span class="muted">({{ r.items.length }})</span>
            @if (r.canAdd && !g.result) { <button class="small" (click)="addItem(r.rel)">+ Ajouter</button> }
          </h4>
          @for (it of r.items; track it.uid) {
            <div class="repeat-item">
              <app-group [group]="it.group" [uid]="it.uid" [kindKey]="null" />
              @if (!g.result) { <button class="link danger small" (click)="removeItem(it.uid)">Retirer {{ it.label.toLowerCase() }}</button> }
            </div>
          }
        </div>
      }
      @for (sg of g.groups; track sg.key) { <app-group [group]="sg" [uid]="uid()" [kindKey]="kindKey()" /> }
      @if (g.unknown.length) {
        <div class="unknown">
          <strong>Données non prévues par le schéma — conservées telles quelles à l'export</strong>
          @for (u of g.unknown; track u.name) {
            <div><code>{{ u.name }}</code> @if (svc.advanced()) { <pre>{{ u.xml }}</pre> }</div>
          }
        </div>
      }
    </fieldset>
  `,
})
export class GroupComponent {
  readonly group = input.required<GroupView>();
  readonly uid = input.required<string>();
  readonly kindKey = input<string | null>(null);
  protected readonly svc = inject(DossierService);

  protected readonly editable = () => this.svc.dossier()?.format.niveauSupport === 'complet';

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
          <div>
            @if (f.parent) { <a href="" class="small" (click)="$event.preventDefault(); goUid(f.parent.uid)">↑ {{ f.parent.label }}</a> }
            <h2>{{ f.title }}</h2>
            <span class="muted">{{ f.kind?.label ?? 'Bloc d\\'informations' }}</span>
            @if (view()?.added) { <span class="badge new">Ajouté</span> } @else if (view()?.modified) { <span class="badge mod">Modifié depuis l'import</span> }
          </div>
          <div class="fiche-actions">
            @if (f.kind && !f.results && editable()) {
              <button (click)="dupOpen.set(true)">Dupliquer</button>
              <button class="danger" (click)="delOpen.set(true)">Supprimer</button>
            }
            @if (closable()) { <button class="link" (click)="closed.emit()" title="Fermer la fiche">✕</button> }
          </div>
        </header>

        @if (svc.advanced()) {
          <div class="tech small">
            <div><span class="muted">Chemin XML :</span> <code>{{ f.xmlPath }}</code></div>
            <div><span class="muted">Chemin XSD :</span> <code>{{ f.path }}</code></div>
            <div><span class="muted">Identifiant interne :</span> <code>{{ f.uid }}</code> @if (view()?.reference) { · <span class="muted">reference :</span> <code>{{ view()!.reference }}</code> }</div>
          </div>
        }

        @if (f.issues.length) {
          <ul class="issues compact">
            @for (i of f.issues; track i.id) {
              <li [class]="'issue g-' + i.gravite" (click)="focusField(i.field)">
                <span class="g">{{ GRAVITE_LABELS[i.gravite] }}</span><span class="msg">{{ i.message }}</span><span class="lvl muted">{{ NIVEAU_LABELS[i.niveau] }}</span>
              </li>
            }
          </ul>
        }

        @if (f.attributes.length) {
          <fieldset class="group">
            <legend>Attributs du document</legend>
            @for (a of f.attributes; track a.name) {
              <div class="field">
                <label>{{ a.name === 'version' ? 'Version du schéma (attribut version)' : 'Attribut ' + a.name }} @if (a.required) { <span class="req">*</span> }</label>
                <div class="control">
                  <input type="text" [value]="a.value ?? ''" (change)="setAttr(a.name, $any($event.target).value)" [placeholder]="a.value === null ? 'non renseigné' : ''" />
                </div>
                @if (a.doc) { <p class="help">{{ a.doc }}</p> }
                @if (a.required && a.value === null) { <p class="field-issue g-avertissement">Attribut obligatoire selon le XSD.</p> }
              </div>
            }
          </fieldset>
        }

        @if (relationsCount() > 0 || f.children.length) {
          <section class="relations">
            <h3>Relations</h3>
            @for (c of f.children; track c.kind.key) {
              <div class="rel">
                <div class="rel-label">{{ c.kind.plural }} <span class="muted">({{ c.items.length }})</span>
                  @if (c.items.length < c.min) { <span class="badge miss">au moins {{ c.min }} requis</span> }
                  @if (editable()) { <button class="small" (click)="addChild(c.kind)">+ Ajouter</button> }
                </div>
                <ul class="rel-items">
                  @for (it of c.items; track it.uid) { <li><a href="" (click)="$event.preventDefault(); goUid(it.uid)">{{ it.title }}</a> <span class="muted small">{{ it.summary }}</span></li> }
                </ul>
              </div>
            }
            @for (r of f.outgoing; track r.label) {
              <div class="rel">
                <div class="rel-label">{{ r.label }} <span class="muted">→</span></div>
                <ul class="rel-items">
                  @for (it of r.items; track it.label) {
                    <li>@if (it.uid) { <a href="" (click)="$event.preventDefault(); goUid(it.uid)">{{ it.label }}</a> <span class="muted small">{{ it.kindLabel }}</span> } @else { <span class="warn-text">{{ it.label }}</span> }</li>
                  }
                </ul>
              </div>
            }
            @for (r of f.incoming; track r.label) {
              <div class="rel">
                <div class="rel-label"><span class="muted">←</span> {{ r.label }} <span class="muted">({{ r.items.length }})</span></div>
                <ul class="rel-items">
                  @for (it of r.items; track it.uid) { <li><a href="" (click)="$event.preventDefault(); goUid(it.uid)">{{ it.label }}</a> <span class="muted small">{{ it.kindLabel }}</span></li> }
                </ul>
              </div>
            }
            @for (r of f.roles; track r.label) {
              <div class="rel">
                <div class="rel-label" [title]="r.doc ?? ''">{{ r.label }} <span class="muted small">(lien par rôle)</span></div>
                <ul class="rel-items">
                  @for (it of r.items; track it.uid) { <li><a href="" (click)="$event.preventDefault(); goUid(it.uid)">{{ it.label }}</a></li> }
                  @empty { <li class="muted small">aucun</li> }
                </ul>
              </div>
            }
          </section>
        }
        @if (addFrom().length && editable()) {
          <div class="add-from">
            @for (a of addFrom(); track a.kind.key + a.refKey) {
              <button (click)="addLinked(a.kind, a.refKey)">+ {{ a.label }}</button>
            }
          </div>
        }

        @for (l of f.links; track l.uid) {
          <p><a href="" (click)="$event.preventDefault(); goUid(l.uid)">→ {{ l.label }}</a></p>
        }

        @for (g of f.groups; track g.key) {
          <app-group [group]="g" [uid]="f.uid" [kindKey]="f.kind?.key ?? null" />
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
  protected readonly GRAVITE_LABELS = GRAVITE_LABELS;
  protected readonly NIVEAU_LABELS = NIVEAU_LABELS;
  protected readonly delOpen = signal(false);
  protected readonly dupOpen = signal(false);

  protected readonly fiche = computed<FicheView | null>(() => {
    this.svc.revision();
    return this.svc.fiche(this.uid());
  });
  protected readonly view = computed(() => this.svc.model()?.objects.get(this.uid()) ?? null);
  protected readonly editable = computed(() => this.svc.dossier()?.format.niveauSupport === 'complet');
  protected readonly closable = computed(() => this.nav.state().tab === 'controles');
  protected readonly relationsCount = computed(() => {
    const f = this.fiche();
    return f ? f.outgoing.length + f.incoming.length + f.roles.length : 0;
  });

  /** Objets que l'on peut créer déjà rattachés à celui-ci (ex. une fenêtre depuis un mur). */
  protected readonly addFrom = computed(() => {
    const k = this.fiche()?.kind;
    if (!k) return [];
    const out: { kind: KindDef; refKey: string; label: string }[] = [];
    for (const r of REFS.filter((x) => x.mode === 'id' && x.toKinds.includes(k.key) && (x.key === 'paroi' || x.key === 'pt1'))) {
      for (const fk of r.fromKinds) {
        const kind = KIND_BY_KEY.get(fk)!;
        out.push({ kind, refKey: r.key, label: `${kind.label} sur cet élément` });
      }
    }
    return out;
  });

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
    const name = kind.nameField ? prompt(`Nom ${kind.article === 'une' ? 'de la' : 'du'} ${kind.label.toLowerCase()} :`, `${kind.label} — ${this.fiche()!.title}`) : '';
    if (name === null) return;
    const uid = this.svc.run(
      (d) => addObject(d, kind.key, { name: name || undefined, link: { refKey, targetUid: this.uid() } }),
      `${kind.label} ajouté(e) et rattaché(e) à « ${this.fiche()!.title} ».`,
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
