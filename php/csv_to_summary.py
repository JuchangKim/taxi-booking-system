import csv
import json
import os

def csv_to_json(csv_file, json_file):
    # Delete old JSON file if it exists
    if os.path.exists(json_file):
        os.remove(json_file)
        print(f"🗑️ Removed old {json_file}")

    data = []
    with open(csv_file, mode='r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            data.append(row)

    with open(json_file, mode='w', encoding='utf-8') as f:
        json.dump(data, f, indent=4)

    print(f"✅ CSV converted to JSON: {json_file}")
    return data

def json_to_pretty_text(data, text_file):
    # Delete old text file if it exists
    if os.path.exists(text_file):
        os.remove(text_file)
        print(f"🗑️ Removed old {text_file}")

    with open(text_file, mode='w', encoding='utf-8') as f:
        f.write("Booking History Summary\n")
        f.write("========================\n\n")
        for booking in data:
            f.write(f"Booking Ref: {booking['Booking Reference']}\n")
            f.write(f"Name       : {booking['Name']}\n")
            f.write(f"Phone      : {booking['Phone']}\n")
            f.write(f"Pickup     : {booking['Pickup Suburb']} on {booking['Date']} at {booking['Time']}\n")
            f.write(f"Destination: {booking['Destination']}\n")
            f.write(f"Status     : {booking['Status']}\n")
            f.write("------------------------------\n")

    print(f"✅ JSON converted to pretty text: {text_file}")

def main():
    csv_file = "booking_history.csv"
    json_file = "booking_history.json"
    text_file = "booking_summary.txt"

    data = csv_to_json(csv_file, json_file)
    json_to_pretty_text(data, text_file)

if __name__ == "__main__":
    main()
