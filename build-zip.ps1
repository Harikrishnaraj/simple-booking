# Builds simple-booking-<version>.zip with a top-level simple-booking/ folder and
# forward-slash paths (Compress-Archive in Windows PowerShell 5.1 writes backslashes,
# which break extraction on Linux hosts).
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem

$src     = Join-Path $PSScriptRoot 'simple-booking'
$version = (Select-String -Path (Join-Path $src 'simple-booking.php') -Pattern "define\( 'SB_VERSION', '([^']+)'").Matches[0].Groups[1].Value
$out     = Join-Path $PSScriptRoot "simple-booking-$version.zip"

if (Test-Path $out) { Remove-Item $out -Confirm:$false }
$zip = [System.IO.Compression.ZipFile]::Open($out, 'Create')
try {
	Get-ChildItem $src -Recurse -File | Where-Object { $_.Extension -ne '.zip' } | ForEach-Object {
		$rel = 'simple-booking/' + $_.FullName.Substring($src.Length + 1).Replace('\', '/')
		[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rel, 'Optimal')
	}
} finally {
	$zip.Dispose()
}
"Built $out"
