import { Component, OnInit } from '@angular/core';
import { BsModalRef } from 'ngx-bootstrap/modal';
import { Subject } from 'rxjs';
import { ApiAzurService } from '../../../services/api-azur.service';
import { ToasterService } from '../../../services/toastr.service';
import { Role } from '../models/role';
import { PermissionsService } from '../../../services/permissions.service';

@Component({
  selector: 'app-core-admin-modal-user',
  templateUrl: './core-admin-modal-add-user.component.html',
  styleUrls: ['./core-admin-modal-add-user.component.scss']
})
export class CoreAdminModalAddUserComponent implements OnInit {

  roles: Role[] = [];
  saving: boolean = false;

  grants: any = {};
  perimeters: any = {};
  arrayPerimeters: any = [];

  users: any[] = [];
  foundUsers: any[] = [];
  selectedFoundUsers: any[] = [];
  selectedUsers: any[] = [];

  domaines: any;
  isNationalWide: boolean = false;
  search: string = "";
  api: string | undefined;

  public onClose: Subject<any> = new Subject();

  constructor(
    private apiAzurService: ApiAzurService,
    private toasterService: ToasterService,
    public bsModalRef: BsModalRef,
    public permissionsService: PermissionsService
  ) { }

  ngOnInit(): void {
    this.apiAzurService.getOnce('getDomains', this.selectCorrectSearchAPI()).then(domaines => {
      this.domaines = domaines;
    })
    this.apiAzurService.getOnce<any>(`getRoles`).then(data => {
      this.roles = data;
    });
  }

  selectCorrectSearchAPI(): string | undefined {
    const fsdum = this.permissionsService.getUser()[4];
    return !this.isNationalWide && (fsdum.substring(0, 4) === '1464' || fsdum === '') ?
      'https://smart-list.place-cloud-enedis.fr/API/' :
      undefined;
  }

  isTrue(value: any) {
    let val = 0;
    if (value == true) {
      val = 1;
    } else {
      val = 0;
    }
    return val;
  }

  onlyNumbers(mixed: string) {
    var res = mixed.replace(/\D/g, "");
    return res;
  }

  save() {
    this.saving = true;

    let rolesList = '';
    for (let i = 0; i < this.roles.length; i++) {
      this.perimeters[this.roles[i].label] = this.arrayPerimeters[i];
      if (this.arrayPerimeters[i]) {
        rolesList += this.roles[i].label + '_' + this.arrayPerimeters[i] + ',';
      }
    }
    const postFields = {
      subject: 'addUsers',
      users: this.users,
      roles: rolesList,
      grants: this.grants,
      perimeters: this.perimeters,
      arrayPerimeters: this.arrayPerimeters
    };
    this.apiAzurService.post<any>(postFields).then(() => {
      this.toasterService.success(`Yes !`, `L'habilitation a été mise à jour.`);
      this.onClose.next('reload');
      this.bsModalRef.hide();
    }).catch(error => {
      alert(error);
    }).finally(() => {
      this.saving = false;
    });
  }

  searchUser() {
    if (this.search.length >= 3) {
      let args = 'agentDirectorySearch';
      args += '&search=' + this.search;
      if (this.isNationalWide) args += '&all=true';
      let delay = 500;
      if (this.isNationalWide) delay = 1000;
      this.apiAzurService.getDelayed(args, delay, this.selectCorrectSearchAPI()).then((foundUsers: any) => {
        this.foundUsers = foundUsers;
      });
    };
  }

  addUser() {
    this.removeUser(this.selectedFoundUsers);
    this.users = [...this.users, ...this.selectedFoundUsers];
    this.selectedFoundUsers = [];
  }

  removeUser(users: any[]) {
    users.forEach(element => {
      this.users = this.users.filter(e => e !== element);
    });
    this.selectedUsers = [];
  }

}
