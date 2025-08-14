// payloads para fluxo de reset
export type RepasswordRequestPayload = {
    email: string
};

export type RepasswordResetPayload = {
    token: string;
    plainPassword: string;
};
