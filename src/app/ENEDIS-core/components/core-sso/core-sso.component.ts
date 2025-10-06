import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiAzurService } from '../../services/api-azur.service';
import { PermissionsService } from '../../services/permissions.service';
import { Link } from '../navbar/link';

@Component({
  selector: 'app-core-sso',
  templateUrl: './core-sso.component.html',
  styleUrls: ['./core-sso.component.scss']
})
export class CoreSSOComponent implements OnInit {

  links: Link[] = [];

  constructor(
    private apiAzurService: ApiAzurService,
    private ativatedRoute: ActivatedRoute,
    private permissionService: PermissionsService
  ) { }

  ngOnInit(): void {
    this.ativatedRoute.queryParams
      .subscribe((params: any) => {
        if (params && params.token) {
          const access_token = window.atob((params.token));
          this.permissionService.setAccessToken(access_token);
          this.permissionService.obtainMyGrants();
        } else {
          this.apiAzurService.get('getMyGrants');
        }
      }
      );
  }

}
