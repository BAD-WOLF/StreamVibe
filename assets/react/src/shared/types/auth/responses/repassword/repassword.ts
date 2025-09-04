export type RepasswordRequestResponse = {
    success: boolean;
    result: { status: string };
};

// response reset: { status: string }
export type RepasswordResetResponse = {
    status: string;
}
