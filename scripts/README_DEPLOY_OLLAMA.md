Deploying taxi-booking-system with Ollama (cloud-init)

This file explains how to launch an Ubuntu EC2 / Lightsail instance using the provided cloud-init file (scripts/cloud-init-ollama.yaml) to install Docker, start the docker-compose stack, and pull a recommended Ollama model (mistral/mistral-7b-instruct).

Quick checklist
- Choose Ubuntu 22.04 LTS (x86_64)
- Recommended instance sizes for Mistral-7B (quantized):
  - Testing: 8 GiB RAM (cheaper, may be tight)
  - Recommended: 16 GiB RAM (comfortable)
  - Disk: 50–100 GB or larger (models + images + DB)
- Security: allow only SSH (22), HTTP (80) and HTTPS (443) publicly. Block MySQL (3306) and Ollama (11434) from public access.

Console (EC2) steps
1. Go to EC2 Launch Instance.
2. Choose an Ubuntu 22.04 LTS AMI (x86_64). Note the AMI ID for your region.
3. Select instance type (e.g., t3.large for testing or m5.xlarge for recommended).
4. Configure instance details:
   - Network and subnet as needed.
   - Under Advanced > User data, paste the contents of scripts/cloud-init-ollama.yaml (or upload it).
5. Add storage: at least 50 GB.
6. Add tags (optional).
7. Configure Security Group:
   - Allow SSH (22) from your IP only.
   - Allow HTTP (80) and HTTPS (443) from 0.0.0.0/0 if you host a public site.
   - Do NOT allow 3306 (MySQL) or 11434 (Ollama) to the public.
8. Choose or create a key pair for SSH.
9. Launch instance.

Console (Lightsail) steps
1. Create instance > Choose Linux/Unix > Ubuntu 22.04.
2. Choose instance plan (8GB or 16GB recommended).
3. In the SSH key / advanced options, provide a startup script: paste scripts/cloud-init-ollama.yaml.
4. Launch.

AWS CLI example (replace placeholders)
# Find a Ubuntu 22.04 AMI for your region (example uses a placeholder)
AMI_ID="ami-0123456789abcdef0"  # replace with correct AMI for region
KEYNAME="MyKeyPair"
SGROUP="sg-0123456789abcdef0"  # security group with ports 22,80,443
SUBNET="subnet-0123456789abcdef0"
INSTANCE_TYPE="m5.xlarge"
USERDATA="$(pwd)/scripts/cloud-init-ollama.yaml"

aws ec2 run-instances \
  --image-id "$AMI_ID" \
  --instance-type "$INSTANCE_TYPE" \
  --key-name "$KEYNAME" \
  --security-group-ids "$SGROUP" \
  --subnet-id "$SUBNET" \
  --user-data file://$USERDATA \
  --block-device-mappings DeviceName=/dev/sda1,Ebs={VolumeSize=80,VolumeType=gp3} \
  --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=taxi-booking-ollama}]'

Notes & troubleshooting
- The cloud-init will create a "deployer" user and install Docker + docker compose plugin.
- The cloud-init attempts to pull mistral/mistral-7b-instruct into the Ollama container. Model pulls can be slow and may fail on small instances due to memory/disk constraints; check instance size and logs.
- If the model pull fails, SSH into the server (as deployer) and run the pull manually:
  sudo docker compose exec -T ollama ollama pull <model-id>
- After pulling a model, the chatbot service reads MODEL_NAME from .env (created by cloud-init). To switch models, update .env and restart the chatbot container:
  sudo nano /home/deployer/taxi-booking-system/aws-cheapest-deployment-options/.env
  sudo docker compose restart chatbot

- To change the model before deployment, edit `MODEL_NAME` in `.env` or in `scripts/cloud-init-ollama.yaml` to the desired Ollama model ID, for example `mistral/mistral-7b-instruct`.

Listing available models with Ollama (local check)
- If you have the Ollama CLI installed on your machine/server, run:
  ollama --version
  ollama list-remote        # lists models available to pull (if supported)
  ollama list              # lists locally installed models

Support
If you want, provide the AWS region and an SSH key pair name and I can produce a ready-to-run AWS CLI command with the correct AMI for that region.
