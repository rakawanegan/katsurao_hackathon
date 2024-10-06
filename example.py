import sys

try:
    input_text = sys.argv[1]
    print(input_text.upper())
except Exception as e:
    print("エラーが発生しました:", str(e), file=sys.stderr)
    sys.exit(1)

