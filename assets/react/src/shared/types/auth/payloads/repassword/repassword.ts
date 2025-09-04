// payloads for the reset flow
export type RepasswordRequestPayload = {
    email: string
};

export type RepasswordResetPayload = {
    token: string;
    plainPassword: string;
};
