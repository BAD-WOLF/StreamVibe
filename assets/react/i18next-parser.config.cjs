const locales = require("./locales.json");

/** @type {import('i18next-parser').I18NextParserOptions} */
module.exports = {
    input: ['src/**/*.{ts,tsx,js,jsx}'],
    output: 'translations/$NAMESPACE.$LOCALE.json',

    defaultNamespace: 'messages',
    namespace: ['messages'],
    ...locales,
    keySeparator: ' ',
    nsSeparator: false,

    useKeysAsDefaultValue: false, // não copia chave crua
    defaultValue: (lng, ns, key) => key.replace(/\./g, ' '),

    lexers: {
        ts: ['JavascriptLexer'],
        tsx: ['JsxLexer'],
        js: ['JavascriptLexer'],
        jsx: ['JsxLexer']
    },

    reactNamespace: true,
    trans: {component: 'Trans', i18nKey: 'i18nKey', defaultsKey: 'defaults'}
};
