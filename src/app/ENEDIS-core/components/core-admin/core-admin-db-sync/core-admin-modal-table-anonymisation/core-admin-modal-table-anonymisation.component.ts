import { Component, OnInit } from '@angular/core';
import { BsModalRef } from 'ngx-bootstrap/modal';

export declare interface Config {
}

export interface Table {
  name: string,
  config: Config
}

@Component({
  selector: 'app-core-admin-modal-table-anonymisation',
  templateUrl: './core-admin-modal-table-anonymisation.component.html',
  styleUrls: ['./core-admin-modal-table-anonymisation.component.scss']
})

export class CoreAdminModalTableAnonymisationComponent implements OnInit {
  table: any;
  dictionaryTypes: any[] = [
    {
      value: 'address',
      label: 'Adresse postale'
    },
    {
      value: 'firstName',
      label: 'Prénom'
    },
    {
      value: 'lastName',
      label: 'Nom de famille'
    }
  ];

  constructor(public bsModalRef: BsModalRef) { }

  ngOnInit() {
    this.table.fields.forEach((field: any) => {
      if (!field.anonymizationConfig) {
        field.anonymizationConfig = {};
      }
    });
  }
}
