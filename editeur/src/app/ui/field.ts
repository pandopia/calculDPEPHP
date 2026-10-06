import { Component, computed, effect, ElementRef, inject, input, signal } from '@angular/core';
import { NgTemplateOutlet } from '@angular/common';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { FieldView, stateLabel } from '../core/metier/model';
import { setFieldState, setReference } from '../core/edition/editor';
import { displayRounded } from '../core/edition/value-codec';
import { ORIGINES } from '../core/metier/sources';

/**
 * Champ en lecture par défaut ; un clic sur la valeur passe en édition.
 * Entrée ou perte du focus valide, Échap annule.
 */
@Component({
  selector: 'app-field',
  imports: [NgTemplateOutlet],
  template: `
    @let f = field();
    <div class="field" [class.editing]="editing()" [class.blank]="showControl() && !editing()" [class.focused]="highlight()" [class.has-error]="hasError()" [class.has-warn]="hasWarn()" [attr.data-rel]="f.rel">
      <div class="f-label" [title]="f.help ?? ''">
        {{ f.label }}@if (f.unit) {<span class="unit"> · {{ f.unit }}</span>}
        @if (origine(); as o) {
          <span class="origine" [class.open]="menuOrigine()">
            <button type="button" class="origine-btn" [class.vide]="!o.code" [attr.data-o]="o.code" (click)="$event.stopPropagation(); menuOrigine.set(!menuOrigine())"
              [title]="o.code ? 'Origine : ' + libelleOrigine(o.code) + ' — cliquer pour changer' : 'Origine de la donnée non renseignée — cliquer pour la renseigner'"
              [attr.aria-label]="'Origine de la donnée : ' + (o.code ? libelleOrigine(o.code) : 'non renseignée')">{{ o.code ? lettre(o.code) : '+' }}</button>
            @if (menuOrigine()) {
              <span class="menu origine-menu" role="menu" (mouseleave)="menuOrigine.set(false)">
                <span class="origine-titre">Origine de la donnée</span>
                @for (x of origines; track x.code) {
                  <button type="button" role="menuitemradio" [attr.aria-checked]="x.code === o.code" [class.on]="x.code === o.code" (click)="choisirOrigine(x.code)"><b [attr.data-o]="x.code">{{ x.lettre }}</b> {{ x.label }}</button>
                }
                @if (o.code) { <button type="button" class="danger" (click)="choisirOrigine(null)">Retirer l'origine</button> }
                @if (o.libelle) { <span class="muted small origine-fiche">Fiche technique : « {{ o.libelle }}@if (o.valeur) {: {{ o.valeur }}} »</span> }
              </span>
            }
          </span>
        }
        @if (f.required && f.state !== 'valeur' && !isReadonly()) { <span class="req" title="Obligatoire">obligatoire</span> }
        @if (f.modified) { <span class="mod-dot" [title]="'Modifié — valeur à l\\'import : ' + (f.before ?? '')"></span> }
      </div>

      @if (!showControl()) {
        <div class="f-row">
          <button type="button" class="f-value" [class.empty]="f.state !== 'valeur'" [class.ro]="isReadonly()" [disabled]="isReadonly()" (click)="edit()">
            @if (f.state === 'valeur') {
              @if (f.input === 'reference' && f.ref?.targets?.length) { <span class="ref-chip">↗ {{ f.ref!.targets[0].label }}</span> }
              @else if (f.input === 'reference') { <span class="muted" [title]="f.raw ?? ''">non décrit dans le dossier</span> }
              @else { {{ shown() }} }
              @if (f.unknownCode) { <span class="chip warn">code inconnu</span> }
            } @else if (f.state === 'nil') { <span class="muted">déclaré nul</span> }
            @else { <span class="muted">{{ isReadonly() ? '—' : '+ Renseigner' }}</span> }
          </button>
          @if (f.input === 'reference' && f.ref?.targets?.length) {
            <a href="" class="small open-link" (click)="$event.preventDefault(); goTarget()">ouvrir</a>
          }
        </div>
      } @else {
        <div class="control">
          @switch (f.input) {
            @case ('reference') {
              <select [id]="id" (change)="onRef($any($event.target).value)" (focus)="editing.set(true)" (blur)="stop()" (keydown.escape)="editing.set(false)">
                <option value="" [selected]="!f.ref?.targets?.length">—</option>
                @if (f.state === 'valeur' && !f.ref?.targets?.length) { <option value="__keep" selected>« {{ f.raw }} » (non résolu, conservé)</option> }
                @for (c of f.ref?.candidates ?? []; track c.uid) { <option [value]="c.uid" [selected]="isTarget(c.uid)">{{ c.label }}</option> }
              </select>
            }
            @case ('enum') { <ng-container *ngTemplateOutlet="sel" /> }
            @case ('oui_non') { <ng-container *ngTemplateOutlet="sel" /> }
            @default {
              <input [id]="id" type="text" [value]="f.state === 'valeur' ? (svc.advanced() ? f.raw : f.display) : ''" [placeholder]="placeholder()"
                     (change)="commit($any($event.target).value, $event)" (focus)="editing.set(true)" (blur)="stop()" (keydown.enter)="$any($event.target).blur()"
                     (keydown.escape)="cancel($event)" [attr.inputmode]="f.input === 'number' || f.input === 'integer' ? 'decimal' : null" />
            }
          }
          <ng-template #sel>
            <select [id]="id" (change)="commit($any($event.target).value, $event); editing.set(false)" (focus)="editing.set(true)" (blur)="stop()" (keydown.escape)="editing.set(false)">
              <option value="" [selected]="f.state !== 'valeur'">—</option>
              @if (f.unknownCode) { <option [value]="f.raw" selected>Code inconnu « {{ f.raw }} » (conservé)</option> }
              @for (o of f.options; track o.code) { <option [value]="o.code" [selected]="o.code === f.raw">{{ svc.advanced() ? o.code + ' — ' : '' }}{{ o.label }}</option> }
            </select>
          </ng-template>
        </div>
        @if (f.help && editing()) { <p class="help">{{ f.help }}</p> }
      }
      @for (i of visibleIssues(); track i.id) { <p [class]="'field-issue g-' + i.gravite">{{ i.message }}</p> }
      @if (error()) { <p class="field-issue g-erreur">{{ error() }}</p> }
      @if (svc.advanced()) { <p class="tech small"><code>{{ f.xmlPath }}</code> · {{ f.state === 'valeur' ? '« ' + f.raw + ' »' : f.state }}</p> }
    </div>
  `,
})
export class FieldComponent {
  readonly field = input.required<FieldView>();
  readonly uid = input.required<string>();
  readonly kindKey = input<string | null>(null);
  readonly readonly = input(false);
  /** valeurs de résultats : affichage arrondi */
  readonly rounded = input(false);
  protected readonly svc = inject(DossierService);
  private readonly nav = inject(NavService);
  private readonly host = inject(ElementRef<HTMLElement>);
  protected readonly error = signal<string | null>(null);
  protected readonly highlight = signal(false);
  protected readonly editing = signal(false);
  protected readonly id = 'f' + Math.random().toString(36).slice(2);
  protected readonly origines = ORIGINES;
  protected readonly menuOrigine = signal(false);
  /** origine de la donnée (fiche technique) : seulement pour une valeur saisie */
  protected readonly origine = computed(() => {
    this.svc.revision();
    const f = this.field();
    if (f.state !== 'valeur' || this.isReadonly()) return null;
    return this.svc.origine(this.uid(), f.rel);
  });

  protected lettre(code: string): string {
    return ORIGINES.find((o) => o.code === code)?.lettre ?? '?';
  }

  protected libelleOrigine(code: string): string {
    return ORIGINES.find((o) => o.code === code)?.label ?? `code ${code}`;
  }

  protected choisirOrigine(code: string | null): void {
    this.menuOrigine.set(false);
    const f = this.field();
    const valeur = f.state === 'valeur' ? (f.display + (f.unit && !f.options.length ? ' ' + f.unit : '')) : null;
    this.svc.definirOrigine(this.uid(), f.rel, code, f.label, valeur);
  }

  protected readonly isReadonly = computed(() => this.readonly() || this.field().readOnly);
  /** champ vide et modifiable : saisie affichée d'emblée ; champ renseigné : lecture, édition au clic */
  protected readonly showControl = computed(() => this.editing() || (!this.isReadonly() && this.field().state !== 'valeur'));
  /** erreurs et avertissements ; « à renseigner » est déjà signalé par l'étiquette « obligatoire » */
  protected readonly visibleIssues = computed(() => this.field().issues.filter((i) => i.gravite !== 'info' && i.niveau !== 'completude'));
  protected readonly hasError = computed(() => this.field().issues.some((i) => i.gravite === 'erreur') || !!this.error());
  protected readonly hasWarn = computed(() => this.field().issues.some((i) => i.gravite === 'avertissement'));
  protected readonly shown = computed(() => {
    const f = this.field();
    if (this.svc.advanced()) return f.raw ?? '';
    if (this.rounded() && (f.input === 'number' || f.input === 'integer') && f.raw !== null) return displayRounded(f.raw, 1);
    return f.display;
  });

  constructor() {
    effect(() => {
      this.nav.focusTick();
      const s = this.nav.state();
      if (s.uid === this.uid() && s.field === this.field().rel) {
        setTimeout(() => {
          const el = this.host.nativeElement as HTMLElement;
          el.closest('details')?.setAttribute('open', '');
          el.scrollIntoView({ block: 'center', behavior: 'smooth' });
          if (!this.isReadonly()) this.edit();
          this.highlight.set(true);
          setTimeout(() => this.highlight.set(false), 2500);
        });
      }
    });
  }

  protected edit(): void {
    if (this.isReadonly()) return;
    this.editing.set(true);
    setTimeout(() => {
      const ctl = this.host.nativeElement.querySelector('input,select') as HTMLInputElement | null;
      ctl?.focus();
      if (ctl instanceof HTMLInputElement) ctl.select();
    });
  }

  protected stop(): void {
    setTimeout(() => {
      if (!this.host.nativeElement.contains(document.activeElement)) {
        this.editing.set(false);
      }
    }, 120);
  }

  protected cancel(e: Event): void {
    const f = this.field();
    (e.target as HTMLInputElement).value = f.state === 'valeur' ? f.display : '';
    this.editing.set(false);
  }

  protected goTarget(): void {
    const t = this.field().ref?.targets[0];
    const m = this.svc.model();
    if (t && m) this.nav.goObject(m, t.uid);
  }

  protected placeholder(): string {
    const f = this.field();
    if (f.input === 'date') return 'JJ/MM/AAAA';
    if (f.input === 'number') return 'ex. 12,5';
    return f.state === 'nil' ? stateLabel('nil') : '';
  }

  protected isTarget(uid: string): boolean {
    return this.field().ref?.targets.some((t) => t.uid === uid) ?? false;
  }

  protected commit(value: string, event: Event): void {
    const f = this.field();
    this.error.set(null);
    if (value.trim() === '') {
      // champ vidé ou « — » choisi : la valeur est retirée (aucune balise à l'export)
      if (f.state !== 'absent') this.svc.run((d) => setFieldState(d, this.uid(), f.rel, 'absent'));
      return;
    }
    if (value === f.raw || (value === f.display && f.state === 'valeur')) return;
    if (!this.svc.setValue(this.uid(), f.rel, value)) {
      this.error.set('Saisie refusée : voir le message.');
      (event.target as HTMLInputElement).value = f.state === 'valeur' ? f.display : '';
    }
  }

  protected onRef(value: string): void {
    if (value === '__keep') return;
    const f = this.field();
    this.svc.run((d) => setReference(d, this.uid(), f.ref!.key, value || null));
    this.editing.set(false);
  }

}
