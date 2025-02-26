#!/bin/bash
#Author ZSec & Eval - eviltw.in
#Thanks for - projectdiscovery - https://projectdiscovery.io/
#Thanks too for - tomnomnom - https://github.com/tomnomnom/ 
trap "exit" INT
while getopts ":d:t:" opt; do
  case $opt in
    d) domain="$OPTARG"
    ;;
    t) template="$OPTARG"
    ;;
    \?) echo "Invalid option -$OPTARG" >&2
    ;;
  esac
done
if [ -z "$domain" ] || [ -z "$template" ]; then
echo "
â–„â–ˆâ–ˆâ–ˆâ–„      â–„   â–„â–ˆ â–ˆ    â–ˆâ–„â–„â–„â–„ â–„â–ˆâ–ˆâ–ˆâ–„   â–„â–ˆâ–„    â–ˆâ–ˆâ–ˆâ–ˆâ–„    â–„
â–ˆâ–€   â–€      â–ˆ  â–ˆâ–ˆ â–ˆ    â–ˆ  â–„â–€ â–ˆâ–€   â–€  â–ˆâ–€ â–€â–„  â–ˆ   â–ˆ     â–ˆ
â–ˆâ–ˆâ–„â–„   â–ˆ     â–ˆ â–ˆâ–ˆ â–ˆ    â–ˆâ–€â–€â–Œ  â–ˆâ–ˆâ–„â–„    â–ˆ   â–€  â–ˆ   â–ˆ â–ˆâ–ˆ   â–ˆ
â–ˆâ–„   â–„â–€ â–ˆ    â–ˆ â–â–ˆ â–ˆâ–ˆâ–ˆâ–„ â–ˆ  â–ˆ  â–ˆâ–„   â–„â–€ â–ˆâ–„  â–„â–€ â–€â–ˆâ–ˆâ–ˆâ–ˆ â–ˆ â–ˆ  â–ˆ
â–€â–ˆâ–ˆâ–ˆâ–€    â–ˆ  â–ˆ   â–     â–€  â–ˆ   â–€â–ˆâ–ˆâ–ˆâ–€   â–€â–ˆâ–ˆâ–ˆâ–€        â–ˆ  â–ˆ â–ˆ
          â–ˆâ–            â–€                         â–ˆ   â–ˆâ–ˆ
          â– ONE FOR ALL SCANNING VULNERABILITY - {v1.1} "
echo ""
echo "Usage: ./evilrecon -d eviltw.in -t /root/template_directory"
exit 1
fi
echo "
â–„â–ˆâ–ˆâ–ˆâ–„      â–„   â–„â–ˆ â–ˆ    â–ˆâ–„â–„â–„â–„ â–„â–ˆâ–ˆâ–ˆâ–„   â–„â–ˆâ–„    â–ˆâ–ˆâ–ˆâ–ˆâ–„    â–„   
â–ˆâ–€   â–€      â–ˆ  â–ˆâ–ˆ â–ˆ    â–ˆ  â–„â–€ â–ˆâ–€   â–€  â–ˆâ–€ â–€â–„  â–ˆ   â–ˆ     â–ˆ  
â–ˆâ–ˆâ–„â–„   â–ˆ     â–ˆ â–ˆâ–ˆ â–ˆ    â–ˆâ–€â–€â–Œ  â–ˆâ–ˆâ–„â–„    â–ˆ   â–€  â–ˆ   â–ˆ â–ˆâ–ˆ   â–ˆ 
â–ˆâ–„   â–„â–€ â–ˆ    â–ˆ â–â–ˆ â–ˆâ–ˆâ–ˆâ–„ â–ˆ  â–ˆ  â–ˆâ–„   â–„â–€ â–ˆâ–„  â–„â–€ â–€â–ˆâ–ˆâ–ˆâ–ˆ â–ˆ â–ˆ  â–ˆ 
â–€â–ˆâ–ˆâ–ˆâ–€    â–ˆ  â–ˆ   â–     â–€  â–ˆ   â–€â–ˆâ–ˆâ–ˆâ–€   â–€â–ˆâ–ˆâ–ˆâ–€        â–ˆ  â–ˆ â–ˆ 
          â–ˆâ–            â–€                         â–ˆ   â–ˆâ–ˆ 
          â– ONE FOR ALL SCANNING VULNERABILITY - {v1.1} "
if [ -d "$domain" ]; then
  i=1
  while [ -d "$domain.$i" ]; do
    i=$((i+1))
  done
  domain="$domain.$i"
fi
mkdir $domain
touch $domain/result-scansubdo.txt
echo "" 
echo "Scanning Sub/Domain"
echo "" 
subfinder -d $domain -all -silent | anew $domain/result-scansubdo.txt
assetfinder -subs-only $domain | anew $domain/result-scansubdo.txt
echo "" 
echo "Scanning Live"
echo "" 
cat $domain/result-scansubdo.txt | httpx -silent | anew $domain/result-scanlive.txt
echo ""
echo "Scanning Vuln"
echo "" 
cat $domain/result-scanlive.txt | nuclei -t $template -severity low,high,medium,critical -silent | anew $domain/result-scanvuln.txt
echo "" 
echo "FINISH check file $domain/result-scanvuln.txt"
exit
