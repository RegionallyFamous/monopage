const owner = "RegionallyFamous";
const repo = "monopage";
const branch = "main";
const blueprint = `https://raw.githubusercontent.com/${owner}/${repo}/${branch}/playground/blueprint.json`;
const playground = `https://playground.wordpress.net/?blueprint-url=${encodeURIComponent(blueprint)}`;

console.log(playground);

