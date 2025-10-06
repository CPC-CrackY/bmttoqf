import { Directive, ElementRef, OnInit, Input } from '@angular/core';
import { PermissionsService } from '../services/permissions.service';

@Directive({
  selector: '[mustBeDisconnectedToDisplay]'
})
export class MustBeDisconnectedToDisplayDirective implements OnInit  {

  @Input('mustBeDisconnectedToDisplay') mustBeDisconnectedToDisplay: any;

  constructor(private el: ElementRef, private permissionsService: PermissionsService) { }

  ngOnInit(): void {
      if (this.mustBeDisconnectedToDisplay) {
        if (this.permissionsService.userIsConnected) {
          this.el.nativeElement.style.display = 'none';
        }
      }
  }

}
