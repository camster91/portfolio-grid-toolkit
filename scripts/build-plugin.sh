#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(cd "${script_dir}/.." && pwd)"
output_dir="${1:-${repo_dir}/dist}"
version="$(sed -n 's/^ \* Version: //p' "${repo_dir}/portfolio-grid-toolkit.php" | head -n 1)"

if [[ -z "${version}" ]]; then
    echo "Could not determine plugin version." >&2
    exit 1
fi

staging_dir="$(mktemp -d)"
trap 'rm -rf "${staging_dir}"' EXIT

package_dir="${staging_dir}/portfolio-grid-toolkit"
mkdir -p "${package_dir}" "${output_dir}"

cp -R "${repo_dir}/assets" "${package_dir}/assets"
cp -R "${repo_dir}/includes" "${package_dir}/includes"
cp "${repo_dir}/LICENSE" "${package_dir}/LICENSE"
cp "${repo_dir}/README.md" "${package_dir}/README.md"
cp "${repo_dir}/portfolio-grid-toolkit.php" "${package_dir}/portfolio-grid-toolkit.php"
cp "${repo_dir}/uninstall.php" "${package_dir}/uninstall.php"

find "${package_dir}" -name '.DS_Store' -delete

archive="${output_dir}/portfolio-grid-toolkit-${version}.zip"
rm -f "${archive}"
(
    cd "${staging_dir}"
    zip -qr "${archive}" portfolio-grid-toolkit
)

unzip -t "${archive}" >/dev/null
echo "${archive}"
