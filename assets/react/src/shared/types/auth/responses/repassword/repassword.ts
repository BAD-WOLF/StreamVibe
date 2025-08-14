export type RepasswordRequestResponse = {
    success: boolean;
    result: { status: string };
};

// resposta reset: { status: string }
export type RepasswordResetResponse = {
    status: string;
}
