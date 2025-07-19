import type {Dispatch, ReactElement, SetStateAction} from "react";

export type InputField = {
    id: "email" | "password";
    type: string;
    placeholder: string;
    value: string;
    setter: Dispatch<SetStateAction<string>>;
    icon: ReactElement;
    error?: string;
}
