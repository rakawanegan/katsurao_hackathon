import sys
import matplotlib.pyplot as plt


temps = [int(temp) for temp in sys.argv[1:]] 

days = [f"Day{i+1}" for i in range(len(temps))]

fig, ax = plt.subplots()

ax.plot(days, temps, marker='o', linestyle='-', color='b')

ax.set_xlabel("Day")
ax.set_ylabel("Temperature")
ax.set_title("Temperature Line Graph")

plt.tight_layout() 
plt.savefig("image/sampleimage.png")
#plt.show()