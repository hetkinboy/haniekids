import { Directive, ElementRef, HostListener, inject } from '@angular/core';

@Directive({
  selector: 'nz-input-number, input[type=number]',
  standalone: true,
})
export class DisableNumberWheelDirective {
  private readonly elementRef = inject<ElementRef<HTMLElement>>(ElementRef);

  @HostListener('wheel')
  onWheel(): void {
    const activeElement = document.activeElement;

    if (activeElement instanceof HTMLElement && this.elementRef.nativeElement.contains(activeElement)) {
      activeElement.blur();
    }
  }
}
