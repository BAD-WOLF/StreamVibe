export type ResetPasswordPayload = {
    token: string;
    plainPassword: string;
}

export type ResetPasswordResponse = {
    status: string;
}
