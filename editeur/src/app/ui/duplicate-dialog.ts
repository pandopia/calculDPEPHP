import { Component, computed, inject, input, output, signal } from '@angular/core';
import { DossierService } from '../services/dossier.service';
import { DuplicationPlan, executeDuplication, planDuplication } from '../core/edition/editor';

@Component({
  selector: 'app-duplicate-dialog',
  template: `
    <div class="modal-backdrop" (click)="done.emit(null)">
      <div class="modal" (click)="$event.stopPropagation()" role="dialog" aria-modal="true">
        @let p = plan();
        @if (p) {
          <h2>Dupliquer {{ p.copies[0].label }}</h2>
          @if (p.openings.length) {
            <label class="check"><input type="checkbox" [checked]="withOpenings()" (change)="withOpenings.set($any($event.target).checked)" />
              Dupliquer aussi les {{ p.openings.length }} ouverture(s) rattachée(s) (rattachées à la copie)</label>
          }
          @if (hasMixte()) {
            <label class="check"><input type="checkbox" [checked]="keepMixte()" (change)="keepMixte.set($any($event.target).checked)" />
              Conserver la référence de générateur mixte sur la copie (la copie partagera le même générateur ECS)</label>
          }
          <h3>Éléments copiés ({{ p.copies.length }})</h3>
          <p class="muted small">Chaque copie reçoit un nouvel identifiant interne et une nouvelle « reference ».</p>
          <ul class="plan">@for (c of p.copies; track c.uid) { <li>{{ c.label }}</li> }</ul>
          @if (p.remapped.length) { <h3>Liens internes redirigés vers les copies</h3><ul class="plan">@for (x of p.remapped; track x) { <li>{{ x }}</li> }</ul> }
          @if (p.shared.length) { <h3>Liens conservés (cibles partagées avec l'original)</h3><ul class="plan">@for (x of p.shared; track x) { <li>{{ x }}</li> }</ul> }
          @if (p.removed.length) { <h3>Liens retirés sur la copie</h3><ul class="plan">@for (x of p.removed; track x) { <li>{{ x }}</li> }</ul> }
          @if (p.notCopied.length) { <h3>Non copiés</h3><ul class="plan">@for (x of p.notCopied; track x) { <li>{{ x }}</li> }</ul> }
          <div class="modal-actions">
            <button (click)="done.emit(null)">Annuler</button>
            <button class="primary" (click)="confirm()">Dupliquer</button>
          </div>
        } @else { <p>{{ error() }}</p><button (click)="done.emit(null)">Fermer</button> }
      </div>
    </div>
  `,
})
export class DuplicateDialogComponent {
  readonly uid = input.required<string>();
  readonly done = output<string | null>();
  private readonly svc = inject(DossierService);
  protected readonly withOpenings = signal(false);
  protected readonly keepMixte = signal(false);
  protected readonly error = signal<string | null>(null);

  protected readonly plan = computed<DuplicationPlan | null>(() => {
    const d = this.svc.dossier();
    if (!d) return null;
    try {
      return planDuplication(d, this.uid(), { withOpenings: this.withOpenings(), keepMixte: this.keepMixte() });
    } catch (e) {
      this.error.set(e instanceof Error ? e.message : String(e));
      return null;
    }
  });

  protected readonly hasMixte = computed(() => {
    const d = this.svc.dossier();
    if (!d) return false;
    const p = planDuplication(d, this.uid(), { withOpenings: this.withOpenings(), keepMixte: true });
    return p.shared.some((x) => /mixte/i.test(x));
  });

  protected confirm(): void {
    const uid = this.svc.run((d) => executeDuplication(d, this.uid(), { withOpenings: this.withOpenings(), keepMixte: this.keepMixte() }), 'Copie créée.');
    this.done.emit(uid ?? null);
  }
}
