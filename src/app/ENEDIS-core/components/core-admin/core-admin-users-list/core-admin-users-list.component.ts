import { Component, OnInit } from '@angular/core';
import { BsModalRef, BsModalService } from 'ngx-bootstrap/modal';
import { ApiAzurService } from '../../../services/api-azur.service';
import { ToasterService } from '../../../services/toastr.service';
import { CoreAdminModalUserComponent } from '../core-admin-modal-user/core-admin-modal-user.component';
import { CoreAdminModalAddUserComponent } from '../core-admin-modal-add-user/core-admin-modal-add-user.component';
import { User } from '../models/user';
import { Role } from '../models/role';

@Component({
  selector: 'app-core-admin-users-list',
  templateUrl: './core-admin-users-list.component.html',
  styleUrls: ['./core-admin-users-list.component.scss']
})
export class CoreAdminUsersListComponent implements OnInit {

  users: User[] = [];
  usersFiltered: User[] = [];
  roles: Role[] = [];
  sanitizedRoles: any[] = [];
  nni = '';
  firstname = '';
  lastname = '';
  helpRequired: boolean = false;
  sortOrder: boolean = false;
  placeholder: string = 'Rechercher par nom, prénom ou NNI';
  searchItem: string = "";
  selectedRole: string | undefined = undefined;

  constructor(private apiAzurService: ApiAzurService, private bsModalService: BsModalService, private toasterService: ToasterService) { }

  ngOnInit(): void {
    this.loadUsers();
    this.loadRoles();
    this.loadSanitizedRoles();
  }

  async loadSanitizedRoles() {
    await this.apiAzurService.getOnce<any>(`getSanitizedRoles`).then(data => { this.sanitizedRoles = data; });
  }

  async loadRoles() {
    await this.apiAzurService.getOnce<any>(`getRoles`).then(data => {
      this.roles = data;
    });
  }

  async loadUsers() {
    await this.apiAzurService.get<any>(`getUsers`).then(data => { this.users = data; this.filterItems(); });
  }

  edit(user: any) {
    const initialState: any = { user: JSON.parse(JSON.stringify(user)) };
    const bsModalRef: BsModalRef<CoreAdminModalUserComponent> = this.bsModalService.show(CoreAdminModalUserComponent, { initialState, class: 'modal-xl' });
    bsModalRef.content?.onClose.subscribe(() => this.loadUsers());
  }

  remove(user: any) {
    this.apiAzurService.post<any>({ subject: 'removeUser', user: user }).then(() => {
      this.loadUsers().then(() => this.toasterService.success(`ok !`, `L'utilisateur a été supprimé.`))
    });
  }

  addUsers() {
    const bsModalRef: BsModalRef<CoreAdminModalAddUserComponent> = this.bsModalService.show(CoreAdminModalAddUserComponent, { class: 'modal-xl' });
    bsModalRef.content?.onClose.subscribe(() => this.loadUsers());
  }

  isBlueCheckbox(user: any, roleLabel: string): boolean {
    if (user.grants[roleLabel]) {
      const userRoles = user.roles.split(',');
      return userRoles.some((role: string) =>
        role.startsWith(roleLabel) && (role.endsWith('_1464M') || role.endsWith('_ENEDIS'))
      );
    }
    return false;
  }

  sort(colName: keyof User, order: boolean) {
    if (order === true) {
      this.usersFiltered.sort((a, b) => a[colName] < b[colName] ? 1 : a[colName] > b[colName] ? -1 : 0)
    } else {
      this.usersFiltered.sort((a, b) => a[colName] > b[colName] ? 1 : a[colName] < b[colName] ? -1 : 0)
    }
    this.sortOrder = !this.sortOrder
  }

  onChangeSearch(): void {
    this.filterItems();
  }

  onChangeSelectedRole() {
    this.filterItems();
  }

  filterItems(): void {
    if (this.searchItem === "" && this.isSelectedRoleEmpty) { this.usersFiltered = this.users; return; }
    this.usersFiltered = this.users.filter((item: User) => {
      const grantsKeys = Object.entries(item.grants)
        .filter(([key, value]) => value) // Keep only the entries where the grant is true
        .map(([key]) => key); // Extract the keys
      const matchingItems = grantsKeys.includes(this.selectedRole!);
      return (item.lastname.toLowerCase().includes(this.searchItem.toLowerCase())
        || item.firstname.toLowerCase().includes(this.searchItem.toLowerCase())
        || item.nni.toLowerCase().includes(this.searchItem.toLowerCase()))
        && (this.isSelectedRoleEmpty ? true : matchingItems)
    });
  }

  private get isSelectedRoleEmpty() {
    return this.selectedRole === undefined
      || this.selectedRole === null;
  }
}
