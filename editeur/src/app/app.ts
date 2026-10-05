import { Component, HostListener, inject } from '@angular/core';
import { DossierService } from './services/dossier.service';
import { AccueilComponent } from './ui/accueil';
import { DossierShellComponent } from './ui/dossier-shell';

@Component({
  selector: 'app-root',
  imports: [AccueilComponent, DossierShellComponent],
  template: `
    @if (svc.dossier()) {
      <app-dossier-shell />
    } @else {
      <app-accueil />
    }
    <div class="toasts" aria-live="polite">
      @for (t of svc.toasts(); track t.id) {
        <div class="toast" [class]="'toast toast-' + t.kind" (click)="svc.dismissToast(t.id)">{{ t.text }}</div>
      }
    </div>
  `,
})
export class App {
  protected readonly svc = inject(DossierService);

  @HostListener('window:keydown', ['$event'])
  onKey(e: KeyboardEvent): void {
    if (!this.svc.dossier()) return;
    const target = e.target as HTMLElement | null;
    if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
      e.preventDefault();
      if (e.shiftKey) this.svc.redo();
      else this.svc.undo();
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
      e.preventDefault();
      this.svc.redo();
    }
  }

  @HostListener('window:beforeunload')
  onUnload(): void {
    this.svc.flushSave();
  }
}
