import { Component, OnInit } from '@angular/core';
import { BsModalRef } from 'ngx-bootstrap/modal';
import { Subject } from 'rxjs';
import { ApiAzurService } from '../../../services/api-azur.service';
import { ToasterService } from '../../../services/toastr.service';
import { PermissionsService } from '../../../services/permissions.service';

@Component({
  selector: 'app-core-admin-modal-user',
  templateUrl: './core-admin-modal-user.component.html',
  styleUrls: ['./core-admin-modal-user.component.scss']
})
export class CoreAdminModalUserComponent implements OnInit {

  roles: { label: string }[] = [];
  user: any;
  domaines: any;
  onClose: Subject<any> = new Subject();
  saving: boolean = false;
  api: string | undefined;

  constructor(
    private apiAzurService: ApiAzurService,
    private toasterService: ToasterService,
    private bsModalRef: BsModalRef,
    private permissionsService: PermissionsService
  ) { }

  ngOnInit(): void {
    this.api = this.permissionsService.getUser()[4].substring(0, 4) === '1464' ?
      'https://smart-list.place-cloud-enedis.fr/API/' :
      undefined;
    this.apiAzurService.getOnce('getDomains', this.api).then(domaines => {
      this.domaines = domaines;
    })
    this.apiAzurService.getOnce<any>(`getRoles`).then((roles: { label: string }[]) => {
      this.roles = roles;
    });
  }

  save() {
    this.saving = true;

    this.user.roles = '';
    for (let i = 0; i < this.roles.length; i++) {
      if (this.user.grants[this.roles[i].label]) {
        const perimeters = this.user.perimeters[this.roles[i].label];
        if (perimeters && Object.prototype.toString.call(perimeters) === '[object Array]') {
          perimeters.forEach((perimeter: string) => {
            this.user.roles += this.roles[i].label + '_' + perimeter + ',';
          });
        } else {
          this.user.grants[this.roles[i].label] = false;
        }
      }
    }
    this.apiAzurService.post<any>({ subject: 'saveUser', user: this.user }).then(() => {
      this.toasterService.success(`Yes !`, `L'habilitation a été mise à jour.`);
      this.onClose.next('reload');
      this.bsModalRef.hide();
    });
  }

}
