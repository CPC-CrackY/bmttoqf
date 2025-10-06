import { Directive, ElementRef, OnInit, Input } from '@angular/core';
import { PermissionsService } from '../services/permissions.service';

@Directive({
  selector: '[mustBeConnectedToDisplay]'
})
export class MustBeConnectedToDisplayDirective implements OnInit  {

  @Input('mustBeConnectedToDisplay') mustBeConnectedToDisplay: any;

  constructor(private el: ElementRef, private permissionsService: PermissionsService) { }

  ngOnInit(): void {
      if (this.mustBeConnectedToDisplay) {
        if (!this.permissionsService.userIsConnected) {
          this.el.nativeElement.style.display = 'none';
        }
      }
  }

}
