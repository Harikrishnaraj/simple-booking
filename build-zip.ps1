# Builds bellbook-<version>.zip with a top-level bellbook/ folder and
# forward-slash paths (Compress-Archive in Windows PowerShell 5.1 writes backslashes,
# which break extraction on Linux hosts).
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem

$src     = Join-Path $PSScriptRoot 'bellbook'
$version = (Select-String -Path (Join-Path $src 'bellbook.php') -Pattern "define\( 'SB_VERSION', '([^']+)'").Matches[0].Groups[1].Value
$out     = Join-Path $PSScriptRoot "bellbook-$version.zip"

if (Test-Path $out) { Remove-Item $out -Confirm:$false }
$zip = [System.IO.Compression.ZipFile]::Open($out, 'Create')
try {
	Get-ChildItem $src -Recurse -File | Where-Object { $_.Extension -ne '.zip' } | ForEach-Object {
		$rel = 'bellbook/' + $_.FullName.Substring($src.Length + 1).Replace('\', '/')
		[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rel, 'Optimal')
	}
} finally {
	$zip.Dispose()
}
"Built $out"
