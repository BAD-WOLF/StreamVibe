#!/usr/bin/env node
import {promises as fs} from "fs";
import * as fsSync from "fs";
import path from "path";
import {spawnSync} from "child_process";

/**
 * Lê argumento --locales:
 * - Primeiro tenta process.env.npm_config_locales (npm passa --locales=... aqui)
 * - Depois tenta argv (--locales=value ou --locales value)
 */
function readLocalesArg() {
    const envVal = process.env.npm_config_locales;
    if (envVal && String(envVal).trim()) return String(envVal);

    const argv = process.argv.slice(2);
    const idx = argv.findIndex(
        (a) => a === "--locales" || a.startsWith("--locales=")
    );
    if (idx === -1) return null;
    if (argv[idx].includes("=")) return argv[idx].split("=")[1] ?? null;
    return argv[idx + 1] ?? null;
}

function parseLocales(raw) {
    if (!raw) return [];
    return raw
        .split(/[,\s]+/)
        .map((s) => s.trim())
        .filter(Boolean);
}

async function updateLocalesFile(newLocales) {
    const filePath = path.resolve(process.cwd(), "locales.json");
    let data = {locales: []};

    // garante que existe o arquivo, se não cria vazio
    if (!fsSync.existsSync(filePath)) {
        await fs.writeFile(filePath, JSON.stringify(data, null, 2), "utf8");
    }

    // agora tenta ler o que tem dentro
    try {
        const txt = await fs.readFile(filePath, "utf8");
        const parsed = JSON.parse(txt);
        if (parsed && Array.isArray(parsed.locales)) {
            data.locales = parsed.locales.slice();
        }
    } catch {
        // se o conteúdo estiver inválido, recria vazio
        data = {locales: []};
        await fs.writeFile(filePath, JSON.stringify(data, null, 2), "utf8");
    }

    // adiciona os novos idiomas
    for (const loc of newLocales) {
        if (!data.locales.includes(loc)) data.locales.push(loc);
    }

    // sobrescreve sempre com a versão atualizada
    await fs.writeFile(filePath, JSON.stringify(data, null, 2), "utf8");
    return data.locales;
}

function runI18nextParser() {
    const cmd = "npx";
    const args = ["i18next", "--config", "i18next-parser.config.cjs"];
    const res = spawnSync(cmd, args, {stdio: "inherit", shell: true});
    return res.status ?? 1;
}

(async function main() {
    try {
        const raw = readLocalesArg();
        const locales = parseLocales(raw);

        if (locales.length) {
            const updated = await updateLocalesFile(locales);
            console.log(`✅ locales.json updated: [${updated.join(", ")}]`);
        } else {
            console.log("ℹ️ no --locales provided; skipping locales.json update.");
        }

        const exitCode = runI18nextParser();
        process.exit(exitCode);
    } catch (err) {
        console.error("❌ i18n-extract failed:", err);
        process.exit(1);
    }
})();
