#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(cd "${script_dir}/.." && pwd)"
output_dir="${1:-${repo_dir}/dist}"
version="$(sed -n 's/^ \* Version: //p' "${repo_dir}/ashbi-portfolio-grid.php" | head -n 1 | tr -d ' ')"

if [[ -z "${version}" ]]; then
    echo "Could not determine plugin version." >&2
    exit 1
fi

staging_dir="$(mktemp -d)"
trap 'rm -rf "${staging_dir}"' EXIT

package_dir="${staging_dir}/ashbi-portfolio-grid"
mkdir -p "${package_dir}" "${output_dir}"

cp -R "${repo_dir}/assets" "${package_dir}/assets"
cp -R "${repo_dir}/includes" "${package_dir}/includes"
cp "${repo_dir}/LICENSE" "${package_dir}/LICENSE"
cp "${repo_dir}/readme.txt" "${package_dir}/readme.txt"
cp "${repo_dir}/ashbi-portfolio-grid.php" "${package_dir}/ashbi-portfolio-grid.php"
cp "${repo_dir}/uninstall.php" "${package_dir}/uninstall.php"

find "${package_dir}" -name '.DS_Store' -delete

archive="${output_dir}/ashbi-portfolio-grid-${version}.zip"
rm -f "${archive}"
(
    cd "${staging_dir}"
    zip -qr "${archive}" ashbi-portfolio-grid
)

unzip -t "${archive}" >/dev/null
echo "${archive}"
