import { Component, OnInit, TemplateRef } from '@angular/core';
import { BsModalService, BsModalRef } from 'ngx-bootstrap/modal';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.scss']
})
export class ModalComponent implements OnInit {

  modalRef?: BsModalRef;

  modalRef2?: BsModalRef | null;
  modalRef3?: BsModalRef;

  modalRef4?: BsModalRef;
  message: string = '';

  toastrOptions = {
    closeButton: true,
    timeOut: 5000,
    extendedTimeOut: 5000,
    enableHtml: true,
    progressBar: true,
    // progressAnimation: 'decreasing',
    positionClass: 'toast-top-right',
    tapToDismiss: true
  };


  constructor(private bsModalService: BsModalService, private toastr: ToastrService) {}

  openModal(template: TemplateRef<any>) {
    this.modalRef = this.bsModalService.show(template);
  }

  openModal2(template: TemplateRef<any>) {
    this.modalRef2 = this.bsModalService.show(template, { class: 'modal-sm' });
  }

  openModal3(template: TemplateRef<any>) {
    this.modalRef3 = this.bsModalService.show(template, { class: 'second' });
  }

  closeFirstModal() {
    if (!this.modalRef2) {
      return;
    }
    this.modalRef2.hide();
    this.modalRef2 = null;
  }

  openModal4(template: TemplateRef<any>) {
    this.modalRef4 = this.bsModalService.show(template, {class: 'second'});
  }

  fakeSaveData(): void {
    this.message = 'Sauvegarde effectuée';
    this.toastr.success('La sauvegarde a <b>réussi</b>.', 'Cool !', this.toastrOptions);
    if (this.modalRef4) this.modalRef4.hide();
  }

  fakeDeclineSaveData(): void {
    this.message = 'Sauvegarde annulée !';
    this.toastr.error('La sauvegarde a été <b>annulée</b>... :(', 'Pourquoi ?', this.toastrOptions);
    if (this.modalRef4) this.modalRef4.hide();
  }

  ngOnInit() {
  }

}
