export interface User {
    nni: string;
    firstname: string;
    lastname: string;
    email?: string;
    roles?: string;
    perimetre?: string;
    creation_date?: string;
    last_successfull_login?: string;
    grants: { [key: string]: boolean };
    perimeters: { [key: string]: boolean } | string[];
    arrayPerimeters: any;
}
