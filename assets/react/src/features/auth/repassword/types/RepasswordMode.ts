export const RepasswordMode: { readonly Request: "request"; readonly Reset: "reset"; readonly Validate: "validate" } = {
    Request: "request",
    Validate: "validate",
    Reset: "reset",
} as const;

export type RepasswordModeType = typeof RepasswordMode[keyof typeof RepasswordMode];