import { Injectable } from '@angular/core';
import { ToastrService, GlobalConfig } from 'ngx-toastr';

export enum TypeToast {
  info = 'info',
  warning = 'warning',
  success = 'success',
  danger = 'danger',
  error = 'error'
}

@Injectable({
  providedIn: 'root'
})

export class ToasterService {

  toastrOptions: Partial<GlobalConfig> = {
    closeButton: true,
    timeOut: 5000,
    extendedTimeOut: 1000,
    enableHtml: true,
    progressBar: true,
    positionClass: 'toast-bottom-center',
    tapToDismiss: true
  };

  constructor(private toastrService: ToastrService) { }

  /**
   * On appel ici un taoster avec les paramètre fournis
   * 
   * @param type Le type de toaster, par défaut info
   * @param titre Le titre du toaster
   * @param message Le message du toaster
   * @param toastrOptions Les options du toaster
   */
  showToast(type: TypeToast, titre: string, message: string, toastrOptions: Partial<GlobalConfig> = this.toastrOptions) {
    switch (type) {
      case TypeToast.info: {
        this.toastrService.info(message, titre, toastrOptions);
        break;
      }
      case TypeToast.warning: {
        this.toastrService.warning(message, titre, toastrOptions);
        break;
      }
      case TypeToast.success: {
        this.toastrService.success(message, titre, toastrOptions);
        break;
      }
      case TypeToast.danger: {
        this.toastrService.error(message, titre, toastrOptions);
        break;
      }
      case TypeToast.error: {
        this.toastrService.error(message, titre, toastrOptions);
        break;
      }
      default: {
        this.toastrService.info(message, titre, toastrOptions);
        break;
      }
    }
  }
  
  info(titre: string, message: string, toastrOptions: Partial<GlobalConfig> = this.toastrOptions) {
    this.toastrService.info(message, titre, toastrOptions);
  }
  
  warning(titre: string, message: string, toastrOptions: Partial<GlobalConfig> = this.toastrOptions) {
    this.toastrService.warning(message, titre, toastrOptions);
  }
  
  success(titre: string, message: string, toastrOptions: Partial<GlobalConfig> = this.toastrOptions) {
    this.toastrService.success(message, titre, toastrOptions);
  }
  
  error(titre: string, message: string, toastrOptions: Partial<GlobalConfig> = this.toastrOptions) {
    this.toastrService.error(message, titre, toastrOptions);
  }
  
  clear(toastId?: number) {
    this.toastrService.clear(toastId);
  }

}
