import i18n from 'i18next';
import {initReactI18next} from 'react-i18next';

const modules: Record<string, any> = import.meta.glob(
    '../../translations/**/*.{json,yaml,yml}',
    {eager: true, import: 'default'}
) as Record<string, any>;

const resources: Record<string, Record<string, any>> = {};
const errors: Array<{ path: string; error: unknown }> = [];

for (const [path, mod] of Object.entries(modules)) {
    try {
        const file: string = path.split('/').pop()!;         // ex.: messages.pt_BR.json
        const base: string = file.replace(/\.(json|yaml|yml)$/i, '');
        const [ns, locale]: string[] = base.split('.');

        const value: any = mod;
        resources[locale] ??= {};
        if (!resources[locale][ns]) resources[locale][ns] = {};
        Object.assign(resources[locale][ns], value);
    } catch (e: unknown) {
        errors.push({path, error: e});
    }
}

const namespaces: string[] = Array.from(
    new Set(Object.values(resources).flatMap((perLocale: Record<string, any>): string[] => Object.keys(perLocale)))
);

const initialLng: string | undefined = (
    (): string | undefined => {
        try {
            const m: RegExpMatchArray | null = window.location.pathname.match(/^\/([^/]+)(\/|$)/);
            const candidate: string | undefined = m?.[1];
            if (candidate && resources[candidate]) return candidate;
        } catch {
            return 'pt_BR';
        }
    }
)();

await i18n.use(initReactI18next).init({
    resources,
    lng: initialLng,
    fallbackLng: 'en',
    ns: namespaces.length ? namespaces : ['messages'],
    defaultNS: 'messages',
    keySeparator: ' ',
    interpolation: {escapeValue: false},
    debug: false, // pode deixar true se quiser logs do i18n
});

declare global {
    interface Window {
        __i18n?: {
            modules: Record<string, any>;
            resources: typeof resources;
            i18n: typeof i18n;
            setLng: (lng: string) => Promise<void>;
            errors: typeof errors;
        };
    }
}

window.__i18n = {
    modules,
    resources,
    i18n,
    errors,
    setLng: async (lng: string): Promise<void> => {
        await i18n.changeLanguage(lng);
    },
};

export default i18n;
