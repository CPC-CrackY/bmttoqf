export class Utilisateur  {

    fsdum: number = 0;
    nni: string = '';
    nom: string = '';
    prenom: string = '';

    constructor(object: any) {
        Object.assign(this, object);
    }

    getPrenom() {
        return this.prenom;
    }

}
