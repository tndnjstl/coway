#!/usr/bin/env python3
"""FTP 배포 스크립트 - sites.xml에서 서버 정보를 읽어 자동 업로드"""

import ftplib
import os
import xml.etree.ElementTree as ET
import base64

# 제외 목록
EXCLUDE = {'.git', '.vscode', '.DS_Store', 'sites.xml', 'ftp_deploy.py', '.gitignore', '.github'}

def load_ftp_config():
    script_dir = os.path.dirname(os.path.abspath(__file__))
    xml_path = os.path.join(script_dir, 'sites.xml')
    tree = ET.parse(xml_path)
    root = tree.getroot()
    server = root.find('.//Server')
    host = server.find('Host').text
    port = int(server.find('Port').text)
    user = server.find('User').text
    pw_b64 = server.find('Pass').text
    password = base64.b64decode(pw_b64).decode('utf-8')
    return host, port, user, password

def upload_dir(ftp, local_path, remote_path='/'):
    for name in os.listdir(local_path):
        if name in EXCLUDE or name.startswith('.'):
            continue
        local_item = os.path.join(local_path, name)
        remote_item = remote_path.rstrip('/') + '/' + name
        if os.path.isdir(local_item):
            try:
                ftp.mkd(remote_item)
            except ftplib.error_perm:
                pass
            upload_dir(ftp, local_item, remote_item)
        else:
            with open(local_item, 'rb') as f:
                ftp.storbinary(f'STOR {remote_item}', f)
            print(f'  업로드: {remote_item}')

def main():
    host, port, user, password = load_ftp_config()
    local_root = os.path.dirname(os.path.abspath(__file__))

    print(f'FTP 접속 중: {host}:{port}')
    ftp = ftplib.FTP()
    ftp.connect(host, port)
    ftp.login(user, password)
    ftp.set_pasv(True)
    print('접속 성공. 업로드 시작...')

    upload_dir(ftp, local_root, '/')

    ftp.quit()
    print('배포 완료!')

if __name__ == '__main__':
    main()
