export type RepasswordRequestPayload = {
    email: string;
}

export type RepasswordRequestResponse = {
    success: boolean;
    result: {
        status: string;
    };
}
