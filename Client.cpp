/*
 * Node.cpp
 *
 *  Created on: Jun 24, 2012
 *      Author: crackers
 */

#include "Client.h"

//Global
const char WEBSOCKET_GUID[] = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

void* Client_initalizeClient(void* self) {
	Client temp = *(Client*)self;
	temp.initalizeClient();
	return NULL;
}

Client::Client(int id) {

	std::cout << "Creating Node " << id << "\n";
	this->socketID = id;
	pthread_t newThread;
	Client* temp = this;
	pthread_create(&newThread, NULL, Client_initalizeClient, (void*)temp);
}

Client::~Client() {
	// TODO Auto-generated destructor stub
}

void Client::parseKey(char* header, int length) {
	char *field = strtok(header,"\r\n");
	char keyword[length];
	memset(&keyword, '\0', length*sizeof(char));
	char *keyval = new char[24];
	this->key = keyval;
	while(field!=NULL) {
		int i = 0;
		int j = 0;
		while(field[i]!='\0') {
			if(field[i]==':') {
				i++;
				break;
			} else if(field[i] == ' ') {

			} else {
				keyword[j] = field[i];
				j++;
			}
			i++;
		}
		if(strcmp((char*)keyword, "Sec-WebSocket-Key")==0) {
			//The current token is the key
			int k = 0;
			while(field[i]!='\0'&&k<24) {
				if(field[i] == ' ') {

				} else {
					keyval[k] = field[i];
					k++;
				}
				i++;
			}
			break;
		}
		memset(&keyword, '\0',length);
		field = strtok(NULL,"\r\n");
	}
}

int Client::getID() {
	return this->socketID;
}

void* Client::initalizeClient() {
	int id = this->socketID;
	char header[1024];
	int len = recv(id,(void*)header,sizeof(char)*2048,0);
	parseKey(&header[0],len);
	return NULL;
}
