import { Component, OnInit } from '@angular/core';
import { ApiAzurService } from '../../../services/api-azur.service';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-core-admin-roles-list',
  templateUrl: './core-admin-roles-list.component.html',
  styleUrls: ['./core-admin-roles-list.component.scss']
})
export class CoreAdminRolesListComponent implements OnInit {

  roles: any[] = [];
  label = '';
  description = '';

  constructor(private apiAzurService: ApiAzurService, private toastrService: ToastrService) { }

  ngOnInit(): void {
    this.loadRoles();
  }

  async loadRoles() {
    await this.apiAzurService.getOnce<any>(`getRoles`).then(data => {
      this.roles = data;
    });
  }

  addRole(): void {
    const msg = 'L\'ajout d\'un rôle se fait via phpMyAdmin puis modification du fichier my-config. '
      + 'Attention : l\'ajout du rôle dans Gardian IDM devra aussi être demandé, le cas échéant, via 2 demandes Spice (rôle Gardian + formulaire).';
    alert(msg);
  }

}
