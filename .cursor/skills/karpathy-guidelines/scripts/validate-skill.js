const fs = require("fs");
const path = require("path");

const skillDir = path.resolve(__dirname, "..");
const skillFile = path.join(skillDir, "SKILL.md");
const referenceFile = path.join(skillDir, "reference.md");

function fail(message) {
    console.error(`ERROR: ${message}`);
    process.exit(1);
}

function read(file) {
    if (!fs.existsSync(file)) {
        fail(`Missing file: ${path.basename(file)}`);
    }
    return fs.readFileSync(file, "utf8");
}

const skill = read(skillFile);
const reference = read(referenceFile);

if (!skill.startsWith("---\n")) {
    fail("SKILL.md must start with YAML frontmatter.");
}

const nameMatch = skill.match(/^name:\s*(.+)$/m);
if (!nameMatch) {
    fail("SKILL.md is missing name metadata.");
}

if (nameMatch[1].trim() !== "karpathy-guidelines") {
    fail("Skill name metadata does not match the expected skill name.");
}

const descriptionMatch = skill.match(/^description:\s*(.+)$/m);
if (!descriptionMatch || !descriptionMatch[1].trim()) {
    fail("SKILL.md is missing a non-empty description.");
}

if (skill.split(/\r?\n/).length > 500) {
    fail("SKILL.md exceeds 500 lines.");
}

if (!skill.includes("[reference.md](reference.md)")) {
    fail(
        "SKILL.md should link to reference.md using a one-level relative link.",
    );
}

if (!skill.includes("[scripts/validate-skill.js](scripts/validate-skill.js)")) {
    fail(
        "SKILL.md should link to the validation script using a one-level relative link.",
    );
}

if (!reference.includes("# Karpathy Guidelines Reference")) {
    fail("reference.md is missing the expected title.");
}

console.log("OK: skill files validated successfully.");
