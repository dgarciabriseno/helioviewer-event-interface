"""
This module provides a server which accepts an observation time, latitude, and longitude (Heliographic Stonyhurst, hgs) and converts them into helioprojective coordinates (hpc).
"""

from astropy.coordinates import SkyCoord
from sunpy.coordinates import frames, transform_with_sun_center
from multiprocessing import Process
import astropy.units as u
import atexit
import logging
import socket
import os

logging.basicConfig(format='%(asctime)s: %(message)s')
logger = logging.getLogger(__name__)
logger.setLevel(logging.INFO)

# This is really all we want to run. But there's way too much python overhead to call this for each coordinate.
# Instead, we create a unix socket and then php can connect to it and send the coordinates to be converted.
def get_hpc(lat, lon, obstime):
    with transform_with_sun_center():
        coord = SkyCoord(lon*u.deg, lat*u.deg, frame=frames.HeliographicStonyhurst, observer="earth", obstime=obstime)
        hpc = coord.transform_to(frames.Helioprojective)
        return f"{hpc.Tx.value}, {hpc.Ty.value}"

def hgs2hpc_thread(connection):
    while True:
        message = connection.recv(1024).decode('utf-8').strip()
        if message == "quit":
            connection.sendall("ok".encode('utf-8'))
            connection.close()
            return
        else:
            try:
                logger.info(f"Processing '{message}'")
                split = message.split(' ')
                result = get_hpc(float(split[0]), float(split[1]), split[2])
                connection.sendall(result.encode('utf-8'))
            except Exception as e:
                failure_msg = "0.123456789 0.987654321"
                connection.sendall(failure_msg.encode('utf-8'))

def remove_socket_file():
    try:
        os.remove("/tmp/hgs2hpc.sock")
    except OSError:
        pass

if __name__ == "__main__":
    # Create unix socket
    sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    # Multiple instances of this socket can exist, so use a unique file name
    sock.bind("/tmp/hgs2hpc.sock")
    # Make socket world accessible
    os.chmod("/tmp/hgs2hpc.sock", 0o777)
    atexit.register(remove_socket_file)
    sock.listen(10000)
    while True:
        # Wait for a connection
        connection, client_address = sock.accept()
        subprocess = Process(target=hgs2hpc_thread, args=(connection,))
        subprocess.start()