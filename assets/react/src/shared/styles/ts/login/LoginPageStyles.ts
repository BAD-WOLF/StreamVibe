export const gradients = {
    gradiente1: "bg-gradient-to-br from-[#667EEA] to-[#764BA2]",
    gradiente2: "bg-gradient-to-b from-[#F7797D] to-[#FBD786]",
};

export const containerClasses = `min-h-screen flex justify-center items-center p-5 ${gradients.gradiente1} font-sans`;
export const cardClasses = `bg-white rounded-2xl w-full max-w-3xl shadow-[0_20px_60px_rgba(0,0,0,0.2)] overflow-hidden`;
export const illustrationClasses = `flex flex-col items-center justify-center text-white p-10 ${gradients.gradiente2}`;
export const formSectionClasses = `px-10 py-12 flex flex-col`;

export const inputContainer = (focused: boolean, hasError: boolean) => {
    console.log(hasError);
    return `relative mb-6 transition-all rounded-lg ${focused ? "ring-4 ring-indigo-400" : ""}`;
};

export const inputClasses = (hasError: boolean) =>
    `w-full px-10 py-3 rounded-lg border-2 text-base outline-none transition-colors focus:border-indigo-500 ${hasError ? "border-red-500" : "border-gray-200"}`;

export const iconBase = "absolute top-1/2 transform -translate-y-1/2 text-gray-400 text-lg";
export const buttonBase =
    `w-full py-4 text-base font-semibold rounded-lg cursor-pointer mt-2 shadow-[0_8px_24px_rgba(0,0,0,0.15)] transition-transform`;
