module.exports = {
  "*.md": ["prettier --write"],
  "*.xml": ["prettier --write --plugin=@prettier/plugin-xml --print-width 200"],
  "*.yml": ["prettier --write"],
  "*.js": ["prettier --write"],
  "*.json": ["prettier --write"],
  "*.php": [
    "vendor/bin/php-cs-fixer fix --quiet --config=.php-cs-fixer.dist.php src",
  ],
};
