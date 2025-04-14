from flask import Flask, request, jsonify
from flask_cors import CORS
from datetime import datetime

app = Flask(__name__)
CORS(app)  # Enable Cross-Origin Resource Sharing

# Stores only the most recent command
latest_command = None

# Human-readable command descriptions
command_descriptions = {
    'lights_power': {
        'on': 'Lights turned ON',
        'off': 'Lights turned OFF'
    },
    'lights_brightness': 'Lights brightness set to {value}%',
    'lights_color': 'Lights color changed to #{value}',
    'ac_power': {
        'on': 'AC turned ON',
        'off': 'AC turned OFF'
    },
    'ac_temp': 'AC temperature set to {value}°C',
    'ac_fan': {
        '1': 'AC fan speed set to LOW',
        '2': 'AC fan speed set to MEDIUM',
        '3': 'AC fan speed set to HIGH'
    },
    'cameras_power': {
        'on': 'Security cameras turned ON',
        'off': 'Security cameras turned OFF'
    },
    'cameras_recording': {
        'start': 'Recording STARTED',
        'stop': 'Recording STOPPED'
    },
    'smartboard_power': {
        'on': 'Smart Board turned ON',
        'off': 'Smart Board turned OFF'
    },
    'smartboard_brightness': 'Smart Board brightness set to {value}%',
    'smartboard_slide': {
        'next': 'Smart Board: Next slide',
        'prev': 'Smart Board: Previous slide'
    },
    'wifi_enable': {
        'enable': 'WiFi ENABLED',
        'disable': 'WiFi DISABLED'
    },
    'wifi_password': 'WiFi password changed',
    'wifi_range': 'WiFi range set to {value}%',
    'speakers_power': {
        'on': 'Speakers turned ON',
        'off': 'Speakers turned OFF'
    },
    'speakers_volume': 'Speaker volume set to {value}%'
}

@app.route('/log_command', methods=['POST'])
def log_command():
    global latest_command
    data = request.json
    
    device = data.get('device')
    action = data.get('action')
    value = data.get('value')
    
    # Generate human-readable message
    message_key = f"{device}_{action}" if action != 'power' else f"{device}_power"
    
    if message_key in command_descriptions:
        if isinstance(command_descriptions[message_key], dict):
            message = command_descriptions[message_key].get(str(value), f"{device} {action}: {value}")
        else:
            message = command_descriptions[message_key].format(value=value)
    else:
        message = f"{device} {action}: {value}"
    
    # Store only this command (overwrite previous)
    latest_command = {
        'timestamp': datetime.now().strftime("%H:%M:%S"),
        'device': device,
        'action': action,
        'value': value,
        'message': message
    }
    
    print(f"[{latest_command['timestamp']}] {message}")
    return jsonify({'status': 'success', 'command': latest_command})

@app.route('/get_commands', methods=['GET'])
def get_commands():
    return jsonify({
        'command': latest_command  # Returns only the single latest command
    })

@app.route('/clear_commands', methods=['POST'])
def clear_commands():
    global latest_command
    latest_command = None
    return jsonify({'status': 'success'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)